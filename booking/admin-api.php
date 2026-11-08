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
 *   GET  poll                — лёгкий опрос: новые заявки и отзывы (для звука и мигающей иконки)
 *   GET  reviews             — отзывы с сайта
 *   POST reviewUpdate        — изменить текст / имя отзыва перед публикацией
 *   POST reviewSetStatus     — опубликовать, отклонить или вернуть на проверку
 *   POST reviewDelete        — удалить отзыв (вместе с вложениями)
 *   POST reviewMediaDelete   — убрать одно вложение (фото или видео) из отзыва
 *   GET  media               — категории, фото и видео главной страницы
 *   POST categoryCreate / categoryRename / categoryDelete — категории фото
 *   POST photoUpload (multipart) / photoUpdate / photosReorder / photoDelete
 *   POST videoChunk (кусками) / videoPoster / videosReorder / videoDelete
 *   GET  stats               — статистика посещений за период (from, to, limit, offset)
 *
 * Все изменения идут через Store::transaction(): блокировка, проверка пересечений
 * и запись выполняются как одна неделимая операция.
 */

declare(strict_types=1);

define('KD_BOOKING', true);
require __DIR__ . '/lib.php';
require __DIR__ . '/content.php';
require __DIR__ . '/stats.php';

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
        'poll'               => ['GET',  'adm_poll'],
        'reviews'            => ['GET',  'adm_reviews'],
        'reviewUpdate'       => ['POST', 'adm_review_update'],
        'reviewSetStatus'    => ['POST', 'adm_review_set_status'],
        'reviewDelete'       => ['POST', 'adm_review_delete'],
        'reviewMediaDelete'  => ['POST', 'adm_review_media_delete'],
        'media'              => ['GET',  'adm_media'],
        'categoryCreate'     => ['POST', 'adm_category_create'],
        'categoryRename'     => ['POST', 'adm_category_rename'],
        'categoryDelete'     => ['POST', 'adm_category_delete'],
        'photoUpload'        => ['POST', 'adm_photo_upload'],
        'photoUpdate'        => ['POST', 'adm_photo_update'],
        'photosReorder'      => ['POST', 'adm_photos_reorder'],
        'photoDelete'        => ['POST', 'adm_photo_delete'],
        'videoChunk'         => ['POST', 'adm_video_chunk'],
        'videoPoster'        => ['POST', 'adm_video_poster'],
        'videosReorder'      => ['POST', 'adm_videos_reorder'],
        'videoDelete'        => ['POST', 'adm_video_delete'],
        'stats'              => ['GET',  'adm_stats'],
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
/* ---------------------------------------------------------------
   Опрос новых событий (звук и мигающая иконка во вкладке браузера)
   --------------------------------------------------------------- */

function adm_poll(): void
{
    $new = 0;
    $maxRequest = 0;
    foreach ((new Store())->read()['requests'] as $r) {
        if (($r['status'] ?? '') === 'new') {
            $new++;
        }
        $maxRequest = max($maxRequest, (int) ($r['number'] ?? 0));
    }
    $pending = 0;
    $maxReview = 0;
    foreach (reviews_store()->read()['reviews'] as $r) {
        if (($r['status'] ?? '') === 'pending') {
            $pending++;
        }
        $maxReview = max($maxReview, (int) ($r['number'] ?? 0));
    }
    ok(['newRequests' => $new, 'maxRequestNumber' => $maxRequest, 'pendingReviews' => $pending, 'maxReviewNumber' => $maxReview]);
}

/* ---------------------------------------------------------------
   Отзывы с сайта: модерация
   --------------------------------------------------------------- */

function adm_review_index(array $data, string $id): int
{
    foreach ($data['reviews'] as $i => $r) {
        if (($r['id'] ?? '') === $id) {
            return (int) $i;
        }
    }
    throw new ApiError('not_found', 'Отзыв не найден.', 404);
}

function adm_reviews(): void
{
    $list = reviews_store()->read()['reviews'];
    usort($list, static fn($a, $b) => ((int) $b['number']) <=> ((int) $a['number']));
    ok(['reviews' => $list]);
}

/** Правка текста и имени. Исходный текст посетителя остаётся в originalText, дата написания не меняется. */
function adm_review_update(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    $text = clean_text($in['text'] ?? '', 2000, true);
    $name = clean_text($in['displayName'] ?? '', 60);
    if ($text === '') {
        throw new ApiError('validation', 'Текст отзыва не может быть пустым.', 422);
    }
    if ($name === '') {
        throw new ApiError('validation', 'Укажите имя, которое увидят на сайте.', 422);
    }
    $review = reviews_store()->transaction(
        static function (array &$data) use ($id, $text, $name): array {
            $i = adm_review_index($data, $id);
            $r = &$data['reviews'][$i];
            if ($r['text'] !== $text || ($r['displayName'] ?? '') !== $name) {
                $r['text'] = $text;
                $r['displayName'] = $name;
                $r['editedAt'] = now_iso();
            }
            return $r;
        },
        ['backup' => 'always']
    );
    ok(['review' => $review]);
}

function adm_review_set_status(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    $status = (string) ($in['status'] ?? '');
    if (!in_array($status, KD_REVIEW_STATUSES, true)) {
        throw new ApiError('bad_status', 'Неизвестный статус отзыва.', 422);
    }
    $review = reviews_store()->transaction(
        static function (array &$data) use ($id, $status): array {
            $i = adm_review_index($data, $id);
            $r = &$data['reviews'][$i];
            if ($r['status'] !== $status) {
                $r['status'] = $status;
                $r['publishedAt'] = $status === 'published' ? now_iso() : null;   // дата написания не трогается
            }
            return $r;
        },
        ['backup' => 'always']
    );
    ok(['review' => $review]);
}

function adm_review_delete(): void
{
    $id = (string) (read_json_body()['id'] ?? '');
    $removed = reviews_store()->transaction(
        static function (array &$data) use ($id): array {
            $i = adm_review_index($data, $id);
            $review = $data['reviews'][$i];
            array_splice($data['reviews'], $i, 1);
            return $review;
        },
        ['backup' => 'always']
    );
    review_media_delete_files((array) ($removed['media'] ?? []));   // файлы вложений удаляются вместе с отзывом
    ok(['deleted' => true]);
}

/** Убрать одно вложение (например, неподходящее фото), не отклоняя отзыв целиком. */
function adm_review_media_delete(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    $file = (string) ($in['file'] ?? '');
    $result = reviews_store()->transaction(
        static function (array &$data) use ($id, $file): array {
            $i = adm_review_index($data, $id);
            $removed = null;
            $keep = [];
            foreach ((array) ($data['reviews'][$i]['media'] ?? []) as $m) {
                if ($removed === null && ($m['file'] ?? '') === $file) {
                    $removed = $m;
                } else {
                    $keep[] = $m;
                }
            }
            if ($removed === null) {
                throw new ApiError('not_found', 'Вложение не найдено.', 404);
            }
            $data['reviews'][$i]['media'] = $keep;
            return ['review' => $data['reviews'][$i], 'removed' => $removed];
        },
        ['backup' => 'always']
    );
    review_media_delete_files([$result['removed']]);
    ok(['review' => $result['review']]);
}

/* ---------------------------------------------------------------
   Фото и видео главной страницы
   --------------------------------------------------------------- */

function adm_media(): void
{
    $data = content_store()->read();
    ok(['categories' => $data['categories'], 'photos' => $data['photos'], 'videos' => $data['videos'], 'limits' => media_limits()]);
}

function adm_category_create(): void
{
    $name = clean_text(read_json_body()['name'] ?? '', 40);
    if ($name === '') {
        throw new ApiError('validation', 'Введите название категории.', 422);
    }
    $category = content_store()->transaction(
        static function (array &$data) use ($name): array {
            if (count($data['categories']) >= 12) {
                throw new ApiError('limit', 'Категорий не может быть больше 12.', 422);
            }
            foreach ($data['categories'] as $c) {
                if (mb_strtolower((string) $c['name']) === mb_strtolower($name)) {
                    throw new ApiError('duplicate', 'Категория с таким названием уже есть.', 409);
                }
            }
            $c = ['id' => 'c' . bin2hex(random_bytes(3)), 'name' => $name];
            $data['categories'][] = $c;
            return $c;
        },
        ['backup' => 'always']
    );
    ok(['category' => $category]);
}

function adm_category_rename(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    $name = clean_text($in['name'] ?? '', 40);
    if ($name === '') {
        throw new ApiError('validation', 'Введите название категории.', 422);
    }
    content_store()->transaction(
        static function (array &$data) use ($id, $name): void {
            foreach ($data['categories'] as &$c) {
                if ($c['id'] === $id) {
                    $c['name'] = $name;
                    return;
                }
            }
            unset($c);
            throw new ApiError('not_found', 'Категория не найдена.', 404);
        },
        ['backup' => 'always']
    );
    ok(['renamed' => true]);
}

/** Удаление категории: её фото переходят в первую из оставшихся категорий (сами фото не удаляются). */
function adm_category_delete(): void
{
    $id = (string) (read_json_body()['id'] ?? '');
    $moved = content_store()->transaction(
        static function (array &$data) use ($id): int {
            if (!category_exists($data, $id)) {
                throw new ApiError('not_found', 'Категория не найдена.', 404);
            }
            if (count($data['categories']) < 2) {
                throw new ApiError('last_category', 'Нельзя удалить последнюю категорию.', 409);
            }
            $data['categories'] = array_values(array_filter($data['categories'], static fn($c) => $c['id'] !== $id));
            $target = (string) $data['categories'][0]['id'];
            $moved = 0;
            foreach ($data['photos'] as &$p) {
                if (($p['category'] ?? '') === $id) {
                    $p['category'] = $target;
                    $moved++;
                }
            }
            unset($p);
            return $moved;
        },
        ['backup' => 'always']
    );
    ok(['movedPhotos' => $moved]);
}

/** Загрузка одного фото (multipart: file, category, size). Клиент шлёт файлы по одному. */
function adm_photo_upload(): void
{
    if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
        throw new ApiError('no_file', 'Файл не дошёл до сервера. Возможно, он больше post_max_size (' . ini_get('post_max_size') . ').', 413);
    }
    $f = $_FILES['file'];
    if ((int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new ApiError('upload', upload_error_message((int) $f['error']), 422);
    }
    if (!is_uploaded_file((string) $f['tmp_name'])) {
        throw new ApiError('upload', 'Некорректная загрузка.', 400);
    }
    if ((int) $f['size'] > media_limits()['maxPhotoBytes']) {
        throw new ApiError('too_large', 'Фото больше ' . (int) cfg('max_photo_mb', 15) . ' МБ.', 413);
    }
    $category = (string) ($_POST['category'] ?? '');
    $size = in_array($_POST['size'] ?? '', KD_PHOTO_SIZES, true) ? (string) $_POST['size'] : 'quarter';
    if (!category_exists(content_store()->read(), $category)) {
        throw new ApiError('bad_category', 'Выберите категорию.', 422);
    }

    $saved = media_save_image((string) $f['tmp_name'], media_ensure_dir('photos'), bin2hex(random_bytes(8)));
    $photo = [
        'id'        => new_id('ph'),
        'file'      => $saved['file'],
        'category'  => $category,
        'size'      => $size,
        'pos'       => '',
        'alt'       => '',
        'w'         => $saved['w'],
        'h'         => $saved['h'],
        'createdAt' => now_iso(),
    ];
    try {
        content_store()->transaction(static function (array &$data) use ($photo): void {
            $data['photos'][] = $photo;
        }, ['backup' => 'hourly']);
    } catch (Throwable $e) {
        media_delete_file($saved['file']);
        throw $e;
    }
    ok(['photo' => $photo]);
}

function adm_photo_update(): void
{
    $in = read_json_body();
    $id = (string) ($in['id'] ?? '');
    $photo = content_store()->transaction(
        static function (array &$data) use ($id, $in): array {
            foreach ($data['photos'] as &$p) {
                if ($p['id'] !== $id) {
                    continue;
                }
                if (isset($in['category'])) {
                    if (!category_exists($data, (string) $in['category'])) {
                        throw new ApiError('bad_category', 'Категория не найдена.', 422);
                    }
                    $p['category'] = (string) $in['category'];
                }
                if (isset($in['size']) && in_array($in['size'], KD_PHOTO_SIZES, true)) {
                    $p['size'] = (string) $in['size'];
                }
                if (isset($in['alt'])) {
                    $p['alt'] = clean_text($in['alt'], 120);
                }
                if (isset($in['pos']) && in_array($in['pos'], ['', 'left', 'right'], true)) {
                    $p['pos'] = (string) $in['pos'];
                }
                return $p;
            }
            throw new ApiError('not_found', 'Фото не найдено.', 404);
        },
        ['backup' => 'hourly']
    );
    ok(['photo' => $photo]);
}

/** Порядок фото внутри категории: ids — идентификаторы в новом порядке. */
function adm_photos_reorder(): void
{
    $in = read_json_body();
    $category = (string) ($in['category'] ?? '');
    $ids = is_array($in['ids'] ?? null) ? array_map('strval', $in['ids']) : [];
    content_store()->transaction(
        static function (array &$data) use ($category, $ids): void {
            $inCat = [];
            $rest = [];
            foreach ($data['photos'] as $p) {
                if (($p['category'] ?? '') === $category) {
                    $inCat[$p['id']] = $p;
                } else {
                    $rest[] = $p;
                }
            }
            $ordered = [];
            foreach ($ids as $pid) {
                if (isset($inCat[$pid])) {
                    $ordered[] = $inCat[$pid];
                    unset($inCat[$pid]);
                }
            }
            $data['photos'] = array_merge($rest, $ordered, array_values($inCat));
        },
        ['backup' => 'hourly']
    );
    ok(['reordered' => true]);
}

function adm_photo_delete(): void
{
    $id = (string) (read_json_body()['id'] ?? '');
    $file = content_store()->transaction(
        static function (array &$data) use ($id): string {
            foreach ($data['photos'] as $i => $p) {
                if ($p['id'] === $id) {
                    array_splice($data['photos'], (int) $i, 1);
                    return (string) ($p['file'] ?? '');
                }
            }
            throw new ApiError('not_found', 'Фото не найдено.', 404);
        },
        ['backup' => 'always']
    );
    media_delete_file($file);   // файлы вне uploads/ (старые фото сайта) остаются на диске
    ok(['deleted' => true]);
}

/** Удаляет брошенные недогруженные видео старше суток. */
function adm_cleanup_tmp(string $dir): void
{
    foreach (glob($dir . '/*.part') ?: [] as $f) {
        if (time() - (int) @filemtime($f) > 86400) {
            @unlink($f);
        }
    }
}

/**
 * Загрузка видео кусками (обходит лимиты upload_max_filesize на хостинге).
 * Запрос: POST videoChunk&uploadId=<16 hex>&offset=<байт>&total=<байт>&name=<имя>, тело — сырые байты куска.
 * Части должны идти по порядку. Последний кусок собирает файл, проверяет тип (MP4 или WebM) и добавляет видео.
 */
function adm_video_chunk(): void
{
    $uploadId = (string) ($_GET['uploadId'] ?? '');
    if (!preg_match('/^[a-f0-9]{16}$/', $uploadId)) {
        throw new ApiError('bad_request', 'Некорректный идентификатор загрузки.', 400);
    }
    $offset = (int) ($_GET['offset'] ?? -1);
    $total = (int) ($_GET['total'] ?? 0);
    $limits = media_limits();
    if ($total < 1 || $total > $limits['maxVideoBytes']) {
        throw new ApiError('too_large', 'Видео больше ' . intdiv($limits['maxVideoBytes'], 1048576) . ' МБ. Сожмите его или увеличьте max_video_mb в config.php.', 413);
    }
    if ($offset < 0 || $offset >= $total) {
        throw new ApiError('bad_request', 'Некорректное смещение.', 400);
    }
    $chunk = file_get_contents('php://input');
    if ($chunk === false || $chunk === '') {
        throw new ApiError('empty_chunk', 'Часть файла не дошла до сервера (post_max_size = ' . ini_get('post_max_size') . ').', 413);
    }
    $len = strlen($chunk);
    if ($offset + $len > $total) {
        throw new ApiError('bad_request', 'Часть выходит за размер файла.', 400);
    }

    $tmpDir = media_ensure_dir('.tmp');
    $part = $tmpDir . '/' . $uploadId . '.part';
    if ($offset === 0) {
        adm_cleanup_tmp($tmpDir);
        if (@file_put_contents($part, $chunk, LOCK_EX) === false) {
            throw new ApiError('storage', 'Не удалось записать файл (нет места на диске?).', 500);
        }
    } else {
        clearstatcache(true, $part);
        if (!is_file($part) || (int) filesize($part) !== $offset) {
            throw new ApiError('chunk_order', 'Нарушен порядок частей файла. Начните загрузку этого видео заново.', 409);
        }
        if (@file_put_contents($part, $chunk, FILE_APPEND | LOCK_EX) === false) {
            throw new ApiError('storage', 'Не удалось записать файл (нет места на диске?).', 500);
        }
    }
    if ($offset + $len < $total) {
        ok(['done' => false, 'received' => $offset + $len]);
    }

    // Последний кусок: проверяем и публикуем
    clearstatcache(true, $part);
    if ((int) filesize($part) !== $total) {
        @unlink($part);
        throw new ApiError('incomplete', 'Файл собрался не полностью. Загрузите видео ещё раз.', 422);
    }
    $head = (string) file_get_contents($part, false, null, 0, 12);
    $ext = null;
    if (strlen($head) >= 8 && substr($head, 4, 4) === 'ftyp') {
        $ext = 'mp4';
    } elseif (str_starts_with($head, "\x1A\x45\xDF\xA3")) {
        $ext = 'webm';
    }
    if ($ext === null) {
        @unlink($part);
        throw new ApiError('bad_video', 'Подходят видео в формате MP4 (H.264) и WebM.', 422);
    }
    $base = bin2hex(random_bytes(8));
    $dest = media_ensure_dir('videos') . '/' . $base . '.' . $ext;
    if (!@rename($part, $dest)) {
        if (!@copy($part, $dest)) {
            throw new ApiError('storage', 'Не удалось сохранить видео.', 500);
        }
        @unlink($part);
    }
    @chmod($dest, 0644);

    $video = [
        'id'        => new_id('vd'),
        'file'      => 'uploads/videos/' . $base . '.' . $ext,
        'poster'    => '',
        'size'      => $total,
        'name'      => clean_text($_GET['name'] ?? '', 120),
        'createdAt' => now_iso(),
    ];
    try {
        content_store()->transaction(static function (array &$data) use ($video): void {
            $data['videos'][] = $video;
        }, ['backup' => 'hourly']);
    } catch (Throwable $e) {
        media_delete_file($video['file']);
        throw $e;
    }
    ok(['done' => true, 'video' => $video]);
}

/** Обложка видео (кадр, снятый в браузере хозяина): multipart poster + id. Необязательна. */
function adm_video_poster(): void
{
    $id = (string) ($_POST['id'] ?? '');
    $f = $_FILES['poster'] ?? null;
    if (!is_array($f) || (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $f['tmp_name'])) {
        throw new ApiError('upload', 'Обложка не получена.', 422);
    }
    if ((int) $f['size'] > 3 * 1048576) {
        throw new ApiError('too_large', 'Обложка больше 3 МБ.', 413);
    }
    $saved = media_save_image((string) $f['tmp_name'], media_ensure_dir('videos'), bin2hex(random_bytes(8)) . '_p', 960, 80);
    try {
        $old = content_store()->transaction(
            static function (array &$data) use ($id, $saved): string {
                foreach ($data['videos'] as &$v) {
                    if ($v['id'] === $id) {
                        $old = (string) ($v['poster'] ?? '');
                        $v['poster'] = $saved['file'];
                        return $old;
                    }
                }
                throw new ApiError('not_found', 'Видео не найдено.', 404);
            }
        );
    } catch (Throwable $e) {
        media_delete_file($saved['file']);
        throw $e;
    }
    media_delete_file($old);
    ok(['poster' => $saved['file']]);
}

function adm_videos_reorder(): void
{
    $in = read_json_body();
    $ids = is_array($in['ids'] ?? null) ? array_map('strval', $in['ids']) : [];
    content_store()->transaction(
        static function (array &$data) use ($ids): void {
            $byId = [];
            foreach ($data['videos'] as $v) {
                $byId[$v['id']] = $v;
            }
            $ordered = [];
            foreach ($ids as $vid) {
                if (isset($byId[$vid])) {
                    $ordered[] = $byId[$vid];
                    unset($byId[$vid]);
                }
            }
            $data['videos'] = array_merge($ordered, array_values($byId));
        },
        ['backup' => 'hourly']
    );
    ok(['reordered' => true]);
}

function adm_video_delete(): void
{
    $id = (string) (read_json_body()['id'] ?? '');
    $video = content_store()->transaction(
        static function (array &$data) use ($id): array {
            foreach ($data['videos'] as $i => $v) {
                if ($v['id'] === $id) {
                    array_splice($data['videos'], (int) $i, 1);
                    return $v;
                }
            }
            throw new ApiError('not_found', 'Видео не найдено.', 404);
        },
        ['backup' => 'always']
    );
    media_delete_file((string) ($video['file'] ?? ''));
    media_delete_file((string) ($video['poster'] ?? ''));
    ok(['deleted' => true]);
}

/* ---------------------------------------------------------------
   Статистика посещений
   --------------------------------------------------------------- */

function adm_stats(): void
{
    $today = (new DateTimeImmutable('now', new DateTimeZone(KD_STATS_TZ)))->format('Y-m-d');
    $to = stats_valid_date($_GET['to'] ?? null) ?? $today;
    $from = stats_valid_date($_GET['from'] ?? null) ?? add_days($to, -29);
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }
    if (nights($from, $to) > 1830) {
        $from = add_days($to, -1830);
    }
    ok(stats_report($from, $to, (int) ($_GET['limit'] ?? 100), (int) ($_GET['offset'] ?? 0)) + ['today' => $today]);
}
