<?php
/**
 * КЛЁВАЯ ДАЧА — API ХОЗЯИНА (вызывается только из admin.php).
 *
 * Каждый запрос должен нести секретный ключ в заголовке X-Admin-Key. Ключ сверяется
 * с config.php на сервере при КАЖДОМ действии (hash_equals). Куки и сессий нет,
 * поэтому подделать запрос с чужого сайта (CSRF) невозможно: заголовок сторонний сайт
 * добавить не может.
 *
 *   GET  overview            — заявки, брони, счётчики, состояние сервиса
 *   GET  request&id=         — подробности заявки
 *   POST updateRequest       — статус и/или внутренняя заметка заявки
 *   POST confirmRequest      — «Отметить даты занятыми» из заявки
 *   POST createBlock         — ручная блокировка дат (без заявки)
 *   POST updateBooking       — изменить период / заметку брони или блокировки
 *   POST cancelBooking       — снять блокировку / отменить бронь
 *   POST resendNotification  — повторить уведомление в Telegram
 *   POST testTelegram        — тестовое сообщение
 *   POST createBackup        — резервная копия JSON
 *
 * Все изменения идут через Store::transaction(): блокировка, проверка пересечений
 * и запись выполняются как одна неделимая операция.
 */

declare(strict_types=1);

define('KD_BOOKING', true);
require __DIR__ . '/lib.php';

kd_run(static function (): void {
    // Доступ: ключ из заголовка. Для чужих — ни слова о том, как устроена защита.
    $given = (string) ($_SERVER['HTTP_X_ADMIN_KEY'] ?? '');
    if (!admin_key_matches($given)) {
        usleep(300000);   // притормаживаем подбор
        log_line('warning', 'Админ-API: неверный или отсутствующий ключ.');
        fail('forbidden', 'Нет доступа.', 403);
    }

    $action = (string) ($_GET['action'] ?? '');
    $routes = [
        // действие => [метод, обработчик]
        'overview'           => ['GET',  'adm_overview'],
        'request'            => ['GET',  'adm_request'],
        'updateRequest'      => ['POST', 'adm_update_request'],
        'confirmRequest'     => ['POST', 'adm_confirm_request'],
        'createBlock'        => ['POST', 'adm_create_block'],
        'updateBooking'      => ['POST', 'adm_update_booking'],
        'cancelBooking'      => ['POST', 'adm_cancel_booking'],
        'resendNotification' => ['POST', 'adm_resend_notification'],
        'testTelegram'       => ['POST', 'adm_test_telegram'],
        'createBackup'       => ['POST', 'adm_create_backup'],
    ];
    if (!isset($routes[$action])) {
        fail('unknown_action', 'Неизвестное действие.', 400);
    }
    [$method, $handler] = $routes[$action];
    require_method($method);
    $handler();
}, true);

/* ---------------------------------------------------------------
   Вспомогательное
   --------------------------------------------------------------- */

function adm_find_request_index(array $data, string $id): int
{
    foreach ($data['requests'] as $i => $r) {
        if (($r['id'] ?? '') === $id) {
            return (int) $i;
        }
    }
    throw new ApiError('not_found', 'Заявка не найдена.', 404);
}

function adm_find_booking_index(array $data, string $id): int
{
    foreach ($data['bookings'] as $i => $b) {
        if (($b['id'] ?? '') === $id) {
            return (int) $i;
        }
    }
    throw new ApiError('not_found', 'Бронирование не найдено.', 404);
}

/** Период и заметка из тела запроса. Возвращает [заезд, выезд, заметка]. */
function adm_period_from(array $in): array
{
    [$a, $b, $err] = check_range($in['checkIn'] ?? null, $in['checkOut'] ?? null, true);
    if ($err !== null) {
        throw new ApiError('invalid_dates', $err, 422);
    }
    return [$a, $b, clean_text($in['note'] ?? '', 500, true)];
}

function adm_booking_view(array $b): array
{
    $b['nights'] = nights((string) $b['checkIn'], (string) $b['checkOut']);
    $b['status'] = $b['status'] ?? 'active';
    return $b;
}

function adm_active_booking_of(array $data, array $request): ?array
{
    if (empty($request['bookingId'])) {
        return null;
    }
    foreach ($data['bookings'] as $b) {
        if ($b['id'] === $request['bookingId'] && booking_is_active($b)) {
            return $b;
        }
    }
    return null;
}

function adm_push_history(array &$request, string $status): void
{
    $request['statusHistory'][] = ['at' => now_iso(), 'status' => $status, 'by' => 'admin'];
}

/* ---------------------------------------------------------------
   Чтение
   --------------------------------------------------------------- */

function adm_overview(): void
{
    $store = new Store();
    $data = $store->read();

    $requests = $data['requests'];
    usort($requests, static fn($a, $b) => ((int) $b['number']) <=> ((int) $a['number']));
    $bookings = array_map('adm_booking_view', $data['bookings']);
    usort($bookings, static fn($a, $b) => strcmp((string) $a['checkIn'], (string) $b['checkIn']));

    $failed = 0;
    foreach ($data['requests'] as $r) {
        if (in_array($r['notificationStatus'] ?? '', ['failed', 'not_configured'], true)) {
            $failed++;
        }
    }

    ok([
        'today'    => today_iso(),
        'requests' => $requests,
        'bookings' => $bookings,
        'health'   => [
            'php'                => PHP_VERSION,
            'curl'               => function_exists('curl_init'),
            'allowUrlFopen'      => (bool) ini_get('allow_url_fopen'),
            'telegramConfigured' => telegram_configured(),
            'failedNotifications' => $failed,
            'file'               => $store->dataFileInfo(),
            'backups'            => $store->backupInfo(),
        ],
    ]);
}

function adm_request(): void
{
    $id = (string) ($_GET['id'] ?? '');
    $data = (new Store())->read();
    $i = adm_find_request_index($data, $id);
    $request = $data['requests'][$i];
    $booking = null;
    if (!empty($request['bookingId'])) {
        foreach ($data['bookings'] as $b) {
            if ($b['id'] === $request['bookingId']) {
                $booking = adm_booking_view($b);
                break;
            }
        }
    }
    ok(['request' => $request, 'booking' => $booking]);
}

/* ---------------------------------------------------------------
   Заявки
   --------------------------------------------------------------- */

/** Смена статуса и/или внутренней заметки. «Подтверждена» ставится только через confirmRequest. */
function adm_update_request(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    $newStatus = array_key_exists('status', $in) ? (string) $in['status'] : null;
    $note = array_key_exists('adminNote', $in) ? clean_text($in['adminNote'], 2000, true) : null;

    if ($newStatus !== null && !in_array($newStatus, KD_STATUSES, true)) {
        throw new ApiError('bad_status', 'Неизвестный статус заявки.', 422);
    }

    $result = (new Store())->transaction(
        static function (array &$data) use ($id, $newStatus, $note): array {
            $i = adm_find_request_index($data, $id);
            $r = &$data['requests'][$i];

            if ($newStatus !== null && $newStatus !== $r['status']) {
                if ($newStatus === 'confirmed') {
                    throw new ApiError('use_confirm', 'Чтобы подтвердить бронь, нажмите «Отметить даты занятыми»: так даты попадут в календарь.', 422);
                }
                $active = adm_active_booking_of($data, $r);
                if ($active !== null) {
                    if ($newStatus !== 'cancelled') {
                        throw new ApiError('has_booking', 'У заявки есть действующая бронь. Чтобы изменить статус, сначала отмените бронирование: даты освободятся.', 409);
                    }
                    $bi = adm_find_booking_index($data, $active['id']);
                    $data['bookings'][$bi]['status'] = 'cancelled';
                    $data['bookings'][$bi]['cancelledAt'] = now_iso();
                    $data['bookings'][$bi]['updatedAt'] = now_iso();
                }
                $r['status'] = $newStatus;
                adm_push_history($r, $newStatus);
            }
            if ($note !== null) {
                $r['adminNote'] = $note;
            }
            $r['updatedAt'] = now_iso();
            return $r;
        },
        ['backup' => 'always']
    );
    ok(['request' => $result]);
}

/** «Отметить даты занятыми»: создаёт бронь из заявки, ставит статус confirmed, связывает записи. */
function adm_confirm_request(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    [$checkIn, $checkOut, $note] = adm_period_from($in);

    $result = (new Store())->transaction(
        static function (array &$data) use ($id, $checkIn, $checkOut, $note): array {
            $i = adm_find_request_index($data, $id);
            if (adm_active_booking_of($data, $data['requests'][$i]) !== null) {
                throw new ApiError('already_confirmed', 'По этой заявке бронь уже создана.', 409);
            }
            $conflicts = find_conflicts($data, $checkIn, $checkOut);
            if ($conflicts) {
                throw conflict_error($conflicts, $data);
            }

            $now = now_iso();
            $booking = [
                'id'        => new_id('bk'),
                'checkIn'   => $checkIn,
                'checkOut'  => $checkOut,
                'source'    => 'request',
                'requestId' => $id,
                'note'      => $note,
                'status'    => 'active',
                'createdAt' => $now,
                'updatedAt' => $now,
            ];
            $data['bookings'][] = $booking;

            $r = &$data['requests'][$i];
            $r['status'] = 'confirmed';
            $r['bookingId'] = $booking['id'];
            $r['updatedAt'] = $now;
            adm_push_history($r, 'confirmed');

            return ['request' => $r, 'booking' => adm_booking_view($booking)];
        },
        ['backup' => 'always']
    );
    ok($result);
}

/* ---------------------------------------------------------------
   Брони и ручные блокировки
   --------------------------------------------------------------- */

function adm_create_block(): void
{
    $in = read_json_body();
    [$checkIn, $checkOut, $note] = adm_period_from($in);

    $booking = (new Store())->transaction(
        static function (array &$data) use ($checkIn, $checkOut, $note): array {
            $conflicts = find_conflicts($data, $checkIn, $checkOut);
            if ($conflicts) {
                throw conflict_error($conflicts, $data);
            }
            $now = now_iso();
            $booking = [
                'id'        => new_id('bk'),
                'checkIn'   => $checkIn,
                'checkOut'  => $checkOut,
                'source'    => 'manual',
                'requestId' => null,
                'note'      => $note,
                'status'    => 'active',
                'createdAt' => $now,
                'updatedAt' => $now,
            ];
            $data['bookings'][] = $booking;
            return adm_booking_view($booking);
        },
        ['backup' => 'always']
    );
    ok(['booking' => $booking]);
}

function adm_update_booking(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    [$checkIn, $checkOut, $note] = adm_period_from($in);

    $booking = (new Store())->transaction(
        static function (array &$data) use ($id, $checkIn, $checkOut, $note): array {
            $i = adm_find_booking_index($data, $id);
            if (!booking_is_active($data['bookings'][$i])) {
                throw new ApiError('not_active', 'Эта бронь уже отменена, изменить её нельзя.', 409);
            }
            $conflicts = find_conflicts($data, $checkIn, $checkOut, $id);   // сама бронь не мешает себе
            if ($conflicts) {
                throw conflict_error($conflicts, $data);
            }
            $data['bookings'][$i]['checkIn'] = $checkIn;
            $data['bookings'][$i]['checkOut'] = $checkOut;
            $data['bookings'][$i]['note'] = $note;
            $data['bookings'][$i]['updatedAt'] = now_iso();
            return adm_booking_view($data['bookings'][$i]);
        },
        ['backup' => 'always']
    );
    ok(['booking' => $booking]);
}

/** Снятие блокировки / отмена брони. Запись остаётся в истории, даты освобождаются. */
function adm_cancel_booking(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');

    $result = (new Store())->transaction(
        static function (array &$data) use ($id): array {
            $i = adm_find_booking_index($data, $id);
            if (!booking_is_active($data['bookings'][$i])) {
                throw new ApiError('not_active', 'Эта бронь уже отменена.', 409);
            }
            $now = now_iso();
            $data['bookings'][$i]['status'] = 'cancelled';
            $data['bookings'][$i]['cancelledAt'] = $now;
            $data['bookings'][$i]['updatedAt'] = $now;

            $request = null;
            $requestId = $data['bookings'][$i]['requestId'] ?? null;
            if ($requestId !== null) {
                foreach ($data['requests'] as $ri => $r) {
                    if ($r['id'] === $requestId && $r['status'] === 'confirmed') {
                        $data['requests'][$ri]['status'] = 'cancelled';
                        $data['requests'][$ri]['updatedAt'] = $now;
                        adm_push_history($data['requests'][$ri], 'cancelled');
                        $request = $data['requests'][$ri];
                        break;
                    }
                }
            }
            return ['booking' => adm_booking_view($data['bookings'][$i]), 'request' => $request];
        },
        ['backup' => 'always']
    );
    ok($result);
}

/* ---------------------------------------------------------------
   Telegram и резервные копии
   --------------------------------------------------------------- */

function adm_resend_notification(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    $res = notify_request($id);
    ok(['sent' => $res['ok'], 'error' => $res['ok'] ? '' : $res['error']]);
}

function adm_test_telegram(): void
{
    $res = telegram_send('Проверка связи: уведомления о заявках с сайта «Клёвая Дача» работают.');
    log_line($res['ok'] ? 'info' : 'warning', 'Тест Telegram: ' . ($res['ok'] ? 'отправлено' : $res['error']));
    ok(['sent' => $res['ok'], 'error' => $res['ok'] ? '' : $res['error']]);
}

function adm_create_backup(): void
{
    $store = new Store();
    $name = $store->backupNow();
    ok(['file' => $name, 'backups' => $store->backupInfo()]);
}
