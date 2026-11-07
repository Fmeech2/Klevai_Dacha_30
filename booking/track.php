<?php
/**
 * КЛЁВАЯ ДАЧА — приём статистики посещений (вызывается скриптом на index.php и mobile.php).
 *
 *   POST track.php  — JSON в теле запроса (подходит и для navigator.sendBeacon):
 *     {"type":"visit", "id":"<UUID>", "utm_source":"", "utm_medium":"", "utm_campaign":"", "utm_content":"",
 *      "referrer":"https://…", "device":"desktop|mobile", "width":1280}
 *     {"type":"click", "id":"<UUID>", "button":"phone"}
 *
 * Данные проверяются и очищаются, частота запросов с одного адреса ограничена (адрес хранится
 * только в виде хеша с секретной солью, см. client_ip_key()). Клик не создаёт нового посетителя.
 * Всегда отвечает коротким JSON: браузеру важно лишь, что запрос дошёл.
 */

declare(strict_types=1);

define('KD_BOOKING', true);
require __DIR__ . '/lib.php';
require __DIR__ . '/content.php';
require __DIR__ . '/stats.php';

kd_run(static function (): void {
    require_method('POST');
    handle_track();
});

function handle_track(): void
{
    $in = read_json_body(4096);

    // Поисковые и служебные роботы статистику не искажают
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|facebookexternalhit|preview|monitor|curl|wget|python|httpclient|scrapy/i', $ua)) {
        ok(['ignored' => true]);
    }

    $type = (string) ($in['type'] ?? '');
    $id = $in['id'] ?? '';
    if (!in_array($type, ['visit', 'click'], true) || !stats_valid_id($id)) {
        throw new ApiError('bad_request', 'Некорректные данные.', 400);
    }
    $id = (string) $id;

    // Ограничение частоты: всего запросов и отдельно новых визитов (хеш адреса, не сам адрес)
    $store = new Store();
    $ipKey = client_ip_key();
    try {
        $okAll = $store->rateLimit('trk:' . $ipKey, 600, 60, 400);
        $okVisit = $type !== 'visit' || $store->rateLimit('trv:' . $ipKey, 3600, 10, 40);
    } catch (StorageError $e) {
        log_line('warning', 'Ограничитель частоты статистики не сработал: ' . $e->getMessage());
        $okAll = $okVisit = true;
    }
    if (!$okAll || !$okVisit) {
        throw new ApiError('rate_limited', 'Слишком много запросов.', 429);
    }

    if ($type === 'visit') {
        $recorded = stats_record_visit($id, $in);
        ok(['recorded' => $recorded]);
    }

    $button = $in['button'] ?? '';
    if (!is_string($button) || !preg_match('/^[a-z0-9_]{1,32}$/', $button)) {
        throw new ApiError('bad_request', 'Некорректное название кнопки.', 400);
    }
    ok(['recorded' => stats_record_click($id, $button)]);
}
