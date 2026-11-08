<?php
/**
 * КЛЁВАЯ ДАЧА — ПУБЛИЧНЫЙ API бронирования (его вызывают index.html и mobile.html).
 *
 *   GET  api.php?action=calendar  — занятые периоды для календаря (только даты)
 *   POST api.php?action=submit    — новая заявка (JSON в теле запроса)
 *   GET  api.php?action=token     — подписанный токен для формы отзыва
 *   POST api.php?action=review    — отзыв с сайта (уходит хозяину на модерацию, на сайте не появляется)
 *   POST api.php?action=reviewUpload — один файл (фото или видео) к отзыву, multipart: file, token
 *
 * Здесь нет ничего административного: ни просмотра заявок, ни изменения дат.
 * Новая заявка НЕ блокирует даты. Даты занимает только хозяин через admin.php.
 */

declare(strict_types=1);

define('KD_BOOKING', true);
require __DIR__ . '/lib.php';
require __DIR__ . '/content.php';

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
        case 'token':
            require_method('GET');
            ok(['formToken' => make_form_token(), 'media' => review_media_limits()]);
            break;
        case 'reviewUpload':
            require_method('POST');
            handle_review_upload();
            break;
        case 'review':
            require_method('POST');
            handle_review();
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
            $errors['phone'] = 'Проверьте номер телефона. Пример: +7 (917) 123-45-67.';
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
/**
 * Отзыв с сайта. Сохраняется как «ждёт публикации»: на сайте он появится, только когда хозяин
 * подтвердит его в админ-панели. Дата написания фиксируется сейчас и потом не меняется.
 */
function handle_review(): void
{
    $in = read_json_body();

    // Ловушка для ботов и подписанный токен — те же, что у формы бронирования
    if (isset($in['website']) && trim((string) $in['website']) !== '') {
        log_line('info', 'Отзыв отброшен: заполнено скрытое поле.');
        ok(['number' => 0]);
    }
    $tokenState = check_form_token((string) ($in['token'] ?? ''));
    if ($tokenState === 'too_fast') {
        throw new ApiError('too_fast', 'Пожалуйста, нажмите «Отправить» ещё раз.', 429);
    }
    if ($tokenState !== 'ok') {
        throw new ApiError('form_expired', 'Форма устарела. Обновите страницу и повторите отправку.', 400);
    }

    [$v, $errors] = review_validate($in);
    if ($errors) {
        throw new ApiError('validation', 'Проверьте заполнение формы.', 422, ['fields' => $errors]);
    }
    // Вложения: идентификаторы файлов, которые уже загружены через reviewUpload тем же токеном
    $mediaIds = [];
    foreach ((array) ($in['media'] ?? []) as $mid) {
        if (is_string($mid) && preg_match('/^up_[a-f0-9]{16}$/', $mid)) {
            $mediaIds[] = $mid;
        }
    }
    if (count($mediaIds) > KD_REVIEW_MAX_PHOTOS + KD_REVIEW_MAX_VIDEOS) {
        throw new ApiError('media', 'Слишком много вложений.', 422);
    }
    $owner = review_media_owner((string) ($in['token'] ?? ''));

    // Не больше 3 отзывов в час и 6 в сутки с одного адреса
    try {
        $allowed = (new Store())->rateLimit('rev:' . client_ip_key(), 3600, 3, 6);
    } catch (StorageError $e) {
        log_line('warning', 'Ограничитель частоты отзывов не сработал: ' . $e->getMessage());
        $allowed = true;
    }
    if (!$allowed) {
        throw new ApiError('rate_limited', 'Слишком много отзывов с вашего адреса. Попробуйте позже.', 429);
    }

    $device = detect_device(is_array($in['device'] ?? null) ? $in['device'] : []);

    $saved = reviews_store()->transaction(
        static function (array &$data) use ($v, $device, $mediaIds, $owner): array {
            // Повторная отправка того же отзыва дубль не создаёт
            foreach ($data['reviews'] as $r) {
                if (($r['firstName'] ?? '') === $v['firstName'] && ($r['originalText'] ?? '') === $v['text']
                    && (int) strtotime((string) ($r['createdAt'] ?? '')) > time() - 600) {
                    return ['duplicate' => true, 'id' => $r['id'], 'number' => $r['number']];
                }
            }
            $number = (int) ($data['meta']['nextNumber'] ?? 0);
            if ($number < 1) {
                $number = 1;
                foreach ($data['reviews'] as $r) {
                    $number = max($number, (int) ($r['number'] ?? 0) + 1);
                }
            }
            $data['meta']['nextNumber'] = $number + 1;
            $now = now_iso();
            review_cleanup_pending($data);
            $media = $mediaIds ? review_claim_media($data, $mediaIds, $owner) : [];
            $review = [
                'id'                 => new_id('rv'),
                'number'             => $number,
                'firstName'          => $v['firstName'],
                'lastName'           => $v['lastName'],
                'phone'              => $v['phone'],
                'displayName'        => review_display_name($v['firstName'], $v['lastName']),
                'text'               => $v['text'],
                'originalText'       => $v['text'],
                'createdAt'          => $now,       // дата написания: её увидят на сайте
                'status'             => 'pending',
                'publishedAt'        => null,
                'editedAt'           => null,
                'consentAt'          => $now,
                'device'             => $device,
                'media'              => $media,
                'notificationStatus' => 'pending',
                'notificationError'  => '',
            ];
            $data['reviews'][] = $review;
            return ['duplicate' => false, 'id' => $review['id'], 'number' => $number];
        },
        ['backup' => 'hourly']
    );

    log_line('info', 'Новый отзыв №' . $saved['number'] . ($saved['duplicate'] ? ' (повтор, дубль не создан)' : ''));
    $response = ['number' => $saved['number']];
    if ($saved['duplicate']) {
        ok($response);
    }
    $reviewId = (string) $saved['id'];
    ok_then($response, static function () use ($reviewId): void {
        review_notify($reviewId);
    });
}
/**
 * Один файл (фото или видео) к отзыву. Форма отправляет файлы по одному, а затем сам отзыв со списком
 * идентификаторов. Тот же подписанный токен, что и у формы, и ограничение частоты по адресу.
 */
function handle_review_upload(): void
{
    $token = (string) ($_POST['token'] ?? '');
    $state = check_form_token($token);
    if ($state === 'too_fast') {
        throw new ApiError('too_fast', 'Пожалуйста, повторите отправку ещё раз.', 429);
    }
    if ($state !== 'ok') {
        throw new ApiError('form_expired', 'Форма устарела. Обновите страницу и повторите отправку.', 400);
    }
    if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
        throw new ApiError('no_file', 'Файл не дошёл до сервера. Возможно, он больше лимита хостинга (post_max_size = ' . ini_get('post_max_size') . ').', 413);
    }
    try {
        $allowed = (new Store())->rateLimit('rvu:' . client_ip_key(), 3600, 30, 80);
    } catch (StorageError $e) {
        log_line('warning', 'Ограничитель загрузок не сработал: ' . $e->getMessage());
        $allowed = true;
    }
    if (!$allowed) {
        throw new ApiError('rate_limited', 'Слишком много загрузок с вашего адреса. Попробуйте позже.', 429);
    }
    $entry = review_upload_file($_FILES['file'], review_media_owner($token));
    ok(['id' => $entry['id'], 'type' => $entry['type']]);
}
