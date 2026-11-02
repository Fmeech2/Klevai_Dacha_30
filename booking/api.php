<?php
/**
 * КЛЁВАЯ ДАЧА — ПУБЛИЧНЫЙ API бронирования (его вызывают index.html и mobile.html).
 *
 *   GET  api.php?action=calendar  — занятые периоды для календаря (только даты)
 *   POST api.php?action=submit    — новая заявка (JSON в теле запроса)
 *
 * Здесь нет ничего административного: ни просмотра заявок, ни изменения дат.
 * Новая заявка НЕ блокирует даты. Даты занимает только хозяин через admin.php.
 */

declare(strict_types=1);

define('KD_BOOKING', true);
require __DIR__ . '/lib.php';

kd_run(static function (): void {
    switch ($_GET['action'] ?? '') {
        case 'calendar':
            require_method('GET');
            handle_calendar();
            break;
        case 'submit':
            require_method('POST');
            handle_submit();
            break;
        default:
            fail('unknown_action', 'Неизвестное действие.', 400);
    }
});

/** Календарь: сегодняшняя дата сервера, занятые периоды без каких-либо данных клиентов, токен формы. */
function handle_calendar(): void
{
    $data = (new Store())->read();
    $today = today_iso();

    // Только действующие брони, которые ещё не закончились
    $periods = [];
    foreach ($data['bookings'] as $b) {
        if (booking_is_active($b) && (string) $b['checkOut'] > $today) {
            $periods[] = [(string) $b['checkIn'], (string) $b['checkOut']];
        }
    }
    sort($periods);

    // Склеиваем соприкасающиеся периоды: посетителю не видно, сколько было отдельных броней
    $merged = [];
    foreach ($periods as [$in, $out]) {
        $last = count($merged) - 1;
        if ($last >= 0 && $in <= $merged[$last][1]) {
            if ($out > $merged[$last][1]) {
                $merged[$last][1] = $out;
            }
        } else {
            $merged[] = [$in, $out];
        }
    }

    ok([
        'today'     => $today,
        'maxDate'   => add_days($today, (int) cfg('booking_horizon_days', 730)),
        'maxNights' => (int) cfg('max_nights', 60),
        'bookings'  => array_map(static fn(array $p): array => ['checkIn' => $p[0], 'checkOut' => $p[1]], $merged),
        'formToken' => make_form_token(),
    ]);
}

/** Приём заявки: проверка, запись в JSON под блокировкой, уведомление в Telegram. */
function handle_submit(): void
{
    $in = read_json_body();

    // 1. Ловушка для ботов: поле «website» человек не видит. Бота обманываем «успехом».
    if (isset($in['website']) && trim((string) $in['website']) !== '') {
        log_line('info', 'Заявка отброшена: заполнено скрытое поле.');
        ok(['requestNumber' => 0]);
    }

    // 2. Подписанный токен формы (выдаётся вместе с календарём)
    $tokenState = check_form_token((string) ($in['token'] ?? ''));
    if ($tokenState === 'too_fast') {
        throw new ApiError('too_fast', 'Пожалуйста, нажмите «Отправить заявку» ещё раз.', 429);
    }
    if ($tokenState !== 'ok') {
        throw new ApiError('form_expired', 'Форма устарела. Закройте окно, откройте его снова и повторите отправку.', 400);
    }

    // 3. Поля формы
    $errors = [];
    $first  = check_name(clean_text($in['firstName'] ?? '', 50), true, 'firstName', 'имя', $errors);
    $last   = check_name(clean_text($in['lastName'] ?? '', 50), false, 'lastName', 'фамилию', $errors);
    $patr   = check_name(clean_text($in['patronymic'] ?? '', 50), false, 'patronymic', 'отчество', $errors);

    $phoneRaw = clean_text($in['phone'] ?? '', 30);
    $phone = null;
    if ($phoneRaw === '') {
        $errors['phone'] = 'Укажите номер телефона, чтобы хозяин мог вам перезвонить.';
    } else {
        $phone = normalize_phone($phoneRaw);
        if ($phone === null) {
            $errors['phone'] = 'Проверьте номер телефона. Пример: +7 917 123-45-67.';
        }
    }

    $comment = clean_text($in['comment'] ?? '', 1000, true);

    if (($in['consent'] ?? false) !== true) {
        $errors['consent'] = 'Необходимо согласие на обработку персональных данных.';
    }

    // 4. Даты
    [$checkIn, $checkOut, $dateError] = check_range($in['checkIn'] ?? null, $in['checkOut'] ?? null, false);
    if ($dateError !== null) {
        $errors['dates'] = $dateError;
    }

    if ($errors) {
        throw new ApiError('validation', 'Проверьте заполнение формы.', 422, ['fields' => $errors]);
    }

    // 5. Ограничение частоты (сбой самого ограничителя заявку не блокирует)
    $rl = (array) cfg('rate_limit', []);
    $store = new Store();
    try {
        $allowed = $store->rateLimit(
            client_ip_key(),
            (int) ($rl['window_sec'] ?? 900),
            (int) ($rl['max_per_window'] ?? 5),
            (int) ($rl['max_per_day'] ?? 20)
        );
    } catch (StorageError $e) {
        log_line('warning', 'Ограничитель частоты не сработал: ' . $e->getMessage());
        $allowed = true;
    }
    if (!$allowed) {
        throw new ApiError('rate_limited', 'Слишком много заявок с вашего адреса. Подождите немного или позвоните хозяину: ' . owner_phone() . '.', 429);
    }

    $client = is_array($in['device'] ?? null) ? $in['device'] : [];
    $device = detect_device($client);

    // 6. Запись. Проверка пересечений — внутри блокировки, вместе с записью.
    $saved = $store->transaction(
        static function (array &$data) use ($first, $last, $patr, $phone, $comment, $checkIn, $checkOut, $device): array {
            // Повторная отправка той же заявки (двойной клик, повтор после обрыва связи) дубль не создаёт
            foreach ($data['requests'] as $r) {
                if (($r['status'] ?? '') === 'new'
                    && ($r['phone'] ?? '') === $phone
                    && ($r['checkIn'] ?? '') === $checkIn
                    && ($r['checkOut'] ?? '') === $checkOut
                    && (int) strtotime((string) ($r['createdAt'] ?? '')) > time() - 600) {
                    return ['duplicate' => true, 'id' => $r['id'], 'number' => $r['number']];
                }
            }

            if (find_conflicts($data, $checkIn, $checkOut)) {
                throw new ApiError(
                    'dates_unavailable',
                    'К сожалению, выбранные даты уже заняты. Календарь обновлён — выберите другие даты.',
                    409
                );
            }

            $number = (int) $data['meta']['nextRequestNumber'];
            $data['meta']['nextRequestNumber'] = $number + 1;
            $now = now_iso();

            $request = [
                'id'                   => new_id('req'),
                'number'               => $number,
                'firstName'            => $first,
                'lastName'             => $last,
                'patronymic'           => $patr,
                'phone'                => $phone,
                'comment'              => $comment,
                'checkIn'              => $checkIn,
                'checkOut'             => $checkOut,
                'status'               => 'new',
                'createdAt'            => $now,
                'updatedAt'            => $now,
                'consentAt'            => $now,
                'device'               => $device,
                'notificationStatus'   => 'pending',
                'notificationError'    => '',
                'notificationAttempts' => 0,
                'notifiedAt'           => null,
                'bookingId'            => null,
                'adminNote'            => '',
                'statusHistory'        => [['at' => $now, 'status' => 'new', 'by' => 'visitor']],
            ];
            $data['requests'][] = $request;

            return ['duplicate' => false, 'id' => $request['id'], 'number' => $number];
        },
        ['backup' => 'hourly']
    );

    log_line('info', 'Новая заявка №' . $saved['number'] . ($saved['duplicate'] ? ' (повтор, дубль не создан)' : ''));

    $response = ['requestNumber' => $saved['number']];
    if ($saved['duplicate']) {
        ok($response);
    }

    // 7. Заявка уже сохранена. Теперь — уведомление. Его сбой заявку не отменяет:
    //    результат записывается в JSON, хозяин увидит заявку в панели и сможет повторить отправку.
    $requestId = (string) $saved['id'];
    ok_then($response, static function () use ($requestId): void {
        notify_request($requestId);
    });
}
