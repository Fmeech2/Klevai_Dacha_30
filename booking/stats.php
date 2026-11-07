<?php
/**
 * КЛЁВАЯ ДАЧА — статистика посещений без сторонних сервисов и без БД.
 *
 * Подключается после lib.php и content.php (из track.php и admin-api.php).
 *
 * Три JSON-файла в data/ (все пишутся через JsonStore: flock + атомарная замена файла):
 *   stats-visitors.json — посетители. Ключ — ID устройства. Повторный визит не создаёт запись,
 *                         а увеличивает visits и обновляет last_seen / last_source; first_source пишется один раз.
 *   stats-time.json     — число визитов по часам (UTC): ключ «2026-10-30T14».
 *                         Из этих счётчиков админка получает час суток, день недели и месяц в нужном часовом
 *                         поясе и может фильтровать по датам (сводные «по часу / по дню недели / по месяцу»
 *                         без даты не позволили бы ни фильтра, ни пересчёта поясов).
 *   stats-links.json    — визиты по источникам, по дням: домены, полные URL, UTM, тип источника.
 */

declare(strict_types=1);

if (!defined('KD_BOOKING')) {
    http_response_code(403);
    exit;
}

/** Часовой пояс, в котором админка показывает время и в котором считаются «дни» для статистики ссылок. */
const KD_STATS_TZ = 'Europe/Astrakhan';

const KD_STATS_MAX_VISITORS = 30000;   // больше — самые давние посетители удаляются
const KD_STATS_MAX_LOG      = 150;     // сколько последних визитов помнить у одного посетителя
const KD_STATS_MAX_BUTTONS  = 24;      // сколько разных кнопок учитывать у одного посетителя
const KD_STATS_MAX_URLS_DAY = 300;     // разных полных URL в сутки (защита от раздувания файла)

const KD_SOURCE_TYPES = ['ad', 'search', 'social', 'other', 'direct'];

/** Поисковики и соцсети: регулярные выражения по домену (без www). */
const KD_STATS_SEARCH = [
    '~(^|\.)google\.[a-z.]{2,7}$~', '~(^|\.)yandex\.[a-z]{2,7}$~', '~^ya\.ru$~', '~(^|\.)bing\.com$~',
    '~(^|\.)duckduckgo\.com$~', '~^(go|search)\.mail\.ru$~', '~(^|\.)rambler\.ru$~', '~(^|\.)yahoo\.[a-z.]{2,7}$~',
    '~(^|\.)baidu\.com$~', '~(^|\.)ecosia\.org$~', '~(^|\.)startpage\.com$~', '~(^|\.)sputnik\.ru$~', '~^search\.brave\.com$~',
];
const KD_STATS_SOCIAL = [
    '~(^|\.)vk\.(com|ru)$~', '~(^|\.)ok\.ru$~', '~(^|\.)t\.me$~', '~(^|\.)telegram\.(org|me)$~', '~(^|\.)facebook\.com$~',
    '~(^|\.)fb\.com$~', '~(^|\.)instagram\.com$~', '~(^|\.)twitter\.com$~', '~^x\.com$~', '~^t\.co$~', '~(^|\.)youtube\.com$~',
    '~^youtu\.be$~', '~(^|\.)dzen\.ru$~', '~(^|\.)tiktok\.com$~', '~(^|\.)pinterest\.[a-z.]{2,7}$~', '~(^|\.)reddit\.com$~',
    '~(^|\.)linkedin\.com$~', '~(^|\.)whatsapp\.com$~', '~^wa\.me$~', '~(^|\.)max\.ru$~', '~(^|\.)threads\.net$~', '~(^|\.)pikabu\.ru$~',
];

/* ---------- хранилища ---------- */

function stats_visitors_store(): JsonStore
{
    return new JsonStore('stats-visitors', ['version' => 1, 'visitors' => []], ['visitors']);
}

function stats_time_store(): JsonStore
{
    return new JsonStore('stats-time', ['version' => 1, 'hourly' => []], ['hourly']);
}

function stats_links_store(): JsonStore
{
    return new JsonStore('stats-links', ['version' => 1, 'days' => []], ['days']);
}

/* ---------- очистка входных данных ---------- */

/** UTM-метка: буквы, цифры и безобидные знаки, не длиннее $max. */
function stats_clean_token(mixed $v, int $max = 100): string
{
    if (!is_string($v)) {
        return '';
    }
    $v = preg_replace('/[^\p{L}\p{N}_.\-+: ]/u', '', trim($v)) ?? '';
    return mb_substr(trim($v), 0, $max);
}

/** Referer: только http(s), без пробелов, управляющих символов и #фрагмента, не длиннее 300 байт. */
function stats_clean_referrer(mixed $v): string
{
    if (!is_string($v) || !preg_match('~^https?://~i', trim($v))) {
        return '';
    }
    $v = preg_replace('/[\x00-\x20\x7F]/', '', trim($v)) ?? '';
    $v = explode('#', $v, 2)[0];
    if (!mb_check_encoding($v, 'UTF-8')) {
        return '';
    }
    return mb_strcut($v, 0, 300, 'UTF-8');
}

/** Домен из адреса: нижний регистр, без www и порта. Пустая строка, если адрес разобрать нельзя. */
function stats_host(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return '';
    }
    $host = strtolower(rtrim($host, '.'));
    return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
}

/** Собственный домен сайта (из заголовка запроса), чтобы переходы «с себя на себя» не считались источником. */
function stats_own_host(): string
{
    $h = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $h = preg_replace('/:\d+$/', '', $h) ?? '';
    return str_starts_with($h, 'www.') ? substr($h, 4) : $h;
}

function stats_host_matches(string $host, array $patterns): bool
{
    foreach ($patterns as $re) {
        if (preg_match($re, $host)) {
            return true;
        }
    }
    return false;
}

/**
 * Тип источника: реклама (есть UTM), поисковик, соцсеть, другой сайт, прямой/неизвестный.
 * Пустой referer и переходы с собственного домена — «прямой/неизвестный».
 */
function stats_source_type(string $domain, string $utmSource, string $utmMedium, string $utmCampaign): string
{
    if ($utmSource !== '' || $utmMedium !== '' || $utmCampaign !== '') {
        return 'ad';
    }
    if ($domain === '') {
        return 'direct';
    }
    if (stats_host_matches($domain, KD_STATS_SEARCH)) {
        return 'search';
    }
    if (stats_host_matches($domain, KD_STATS_SOCIAL)) {
        return 'social';
    }
    return 'other';
}

/** Источник визита в том виде, в каком он хранится у посетителя. */
function stats_build_source(array $in): array
{
    $referer = stats_clean_referrer($in['referrer'] ?? '');
    $domain  = $referer !== '' ? stats_host($referer) : '';
    if ($domain !== '' && $domain === stats_own_host()) {
        $referer = '';
        $domain = '';
    }
    $utm = [
        'utm_source'   => stats_clean_token($in['utm_source'] ?? ''),
        'utm_medium'   => stats_clean_token($in['utm_medium'] ?? ''),
        'utm_campaign' => stats_clean_token($in['utm_campaign'] ?? ''),
        'utm_content'  => stats_clean_token($in['utm_content'] ?? ''),
    ];
    return [
        'type'    => stats_source_type($domain, $utm['utm_source'], $utm['utm_medium'], $utm['utm_campaign']),
        'domain'  => $domain,
        'referer' => $referer,
    ] + $utm;
}

/* ---------- запись ---------- */

function stats_valid_id(mixed $id): bool
{
    return is_string($id) && (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id);
}

/**
 * Новый визит. Возвращает true, если визит записан (false — дубль от второй вкладки).
 * $in: id, utm_*, referrer, device ('desktop'|'mobile'), width.
 */
function stats_record_visit(string $id, array $in): bool
{
    $now = time();
    $iso = gmdate('Y-m-d\TH:i:s\Z', $now);
    $source = stats_build_source($in);
    $device = [
        'type'   => (($in['device'] ?? '') === 'mobile') ? 'mobile' : 'desktop',
        'screen' => max(0, min(10000, (int) ($in['width'] ?? 0))),
    ];

    $recorded = stats_visitors_store()->transaction(
        static function (array &$d) use ($id, $now, $iso, $source, $device): bool {
            if (isset($d['visitors'][$id])) {
                $v = &$d['visitors'][$id];
                $log = (array) ($v['visit_log'] ?? []);
                $lastTs = $log ? (int) strtotime((string) end($log)) : 0;
                if ($now - $lastTs < 60) {
                    return false;                       // две вкладки отправили одно и то же
                }
                $v['visits'] = (int) ($v['visits'] ?? 0) + 1;
                $v['last_seen'] = $iso;
                $v['last_source'] = $source;
                $v['device'] = $device;
                $log[] = $iso;
                $v['visit_log'] = array_slice($log, -KD_STATS_MAX_LOG);
                unset($v);
                return true;
            }

            if (count($d['visitors']) >= KD_STATS_MAX_VISITORS) {
                // Освобождаем место: убираем 5% самых давних по last_seen
                uasort($d['visitors'], static fn($a, $b) => strcmp((string) ($a['last_seen'] ?? ''), (string) ($b['last_seen'] ?? '')));
                $d['visitors'] = array_slice($d['visitors'], (int) (KD_STATS_MAX_VISITORS * 0.05), null, true);
            }
            $d['visitors'][$id] = [
                'first_seen'   => $iso,
                'last_seen'    => $iso,
                'visits'       => 1,
                'first_source' => $source,      // пишется один раз и больше не меняется
                'last_source'  => $source,
                'device'       => $device,
                'clicks'       => [],
                'visit_log'    => [$iso],
            ];
            return true;
        },
        ['backup' => 'hourly']
    );
    if (!$recorded) {
        return false;
    }

    // Время: счётчик по часу (UTC)
    $hourKey = gmdate('Y-m-d\TH', $now);
    stats_time_store()->transaction(static function (array &$d) use ($hourKey): void {
        $d['hourly'][$hourKey] = (int) ($d['hourly'][$hourKey] ?? 0) + 1;
    });

    // Ссылки: счётчики за сутки (по поясу KD_STATS_TZ)
    $day = (new DateTimeImmutable('@' . $now))->setTimezone(new DateTimeZone(KD_STATS_TZ))->format('Y-m-d');
    stats_links_store()->transaction(static function (array &$d) use ($day, $source): void {
        $row = $d['days'][$day] ?? ['types' => [], 'domains' => [], 'urls' => [], 'utm' => []];
        $row['types'][$source['type']] = (int) ($row['types'][$source['type']] ?? 0) + 1;
        if ($source['domain'] !== '') {
            $dom = $row['domains'][$source['domain']] ?? ['n' => 0, 'type' => $source['type']];
            $dom['n']++;
            $row['domains'][$source['domain']] = $dom;
        }
        if ($source['referer'] !== '' && (isset($row['urls'][$source['referer']]) || count($row['urls']) < KD_STATS_MAX_URLS_DAY)) {
            $u = $row['urls'][$source['referer']] ?? ['n' => 0, 'type' => $source['type'], 'domain' => $source['domain']];
            $u['n']++;
            $row['urls'][$source['referer']] = $u;
        }
        if ($source['type'] === 'ad') {
            $key = $source['utm_source'] . '|' . $source['utm_medium'] . '|' . $source['utm_campaign'];
            $row['utm'][$key] = (int) ($row['utm'][$key] ?? 0) + 1;
        }
        $d['days'][$day] = $row;
    });
    return true;
}

/** Клик по кнопке: увеличивает счётчик у существующего посетителя. Новых записей не создаёт. */
function stats_record_click(string $id, string $button): bool
{
    return (bool) stats_visitors_store()->transaction(static function (array &$d) use ($id, $button): bool {
        if (!isset($d['visitors'][$id])) {
            return false;
        }
        $clicks = (array) ($d['visitors'][$id]['clicks'] ?? []);
        if (!isset($clicks[$button]) && count($clicks) >= KD_STATS_MAX_BUTTONS) {
            return false;
        }
        $clicks[$button] = (int) ($clicks[$button] ?? 0) + 1;
        $d['visitors'][$id]['clicks'] = $clicks;
        return true;
    });
}

/* ---------- отчёт для админ-панели ---------- */

/** Дата YYYY-MM-DD или null. */
function stats_valid_date(mixed $s): ?string
{
    return parse_iso_date($s);
}

/**
 * Сводный отчёт за период. $from и $to — даты (включительно) в поясе KD_STATS_TZ.
 * Все блоки считаются по одному периоду.
 */
function stats_report(string $from, string $to, int $limit = 100, int $offset = 0): array
{
    $tz  = new DateTimeZone(KD_STATS_TZ);
    $utc = new DateTimeZone('UTC');
    $fromTs = (new DateTimeImmutable($from . ' 00:00:00', $tz))->getTimestamp();
    $toTs   = (new DateTimeImmutable($to . ' 23:59:59', $tz))->getTimestamp();
    $local  = static fn(int $ts): string => (new DateTimeImmutable('@' . $ts))->setTimezone($tz)->format('Y-m-d H:i');

    // --- время захода: часы / дни недели / месяцы ---
    $hours = array_fill(0, 24, 0);
    $weekdays = array_fill(0, 7, 0);
    $months = [];
    foreach (stats_time_store()->read()['hourly'] as $key => $n) {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d\TH', (string) $key, $utc);
        if ($dt === false) {
            continue;
        }
        $ts = $dt->getTimestamp();
        if ($ts < $fromTs || $ts > $toTs) {
            continue;
        }
        $l = $dt->setTimezone($tz);
        $hours[(int) $l->format('G')] += (int) $n;
        $weekdays[(int) $l->format('N') - 1] += (int) $n;
        $mk = $l->format('Y-m');
        $months[$mk] = ($months[$mk] ?? 0) + (int) $n;
    }
    // Месяцы без визитов внутри периода показываем нулями, чтобы график не «проваливался»
    $cur = new DateTimeImmutable($from . ' 00:00:00', $tz);
    $cur = $cur->modify('first day of this month');
    $endMonth = (new DateTimeImmutable($to . ' 00:00:00', $tz))->format('Y-m');
    for ($i = 0; $i < 60; $i++) {
        $mk = $cur->format('Y-m');
        $months[$mk] = $months[$mk] ?? 0;
        if ($mk >= $endMonth) {
            break;
        }
        $cur = $cur->modify('+1 month');
    }
    ksort($months);

    // --- посетители ---
    $unique = 0;
    $visitsTotal = 0;
    $repeat = 0;
    $clicksTotal = [];
    $rows = [];
    foreach (stats_visitors_store()->read()['visitors'] as $id => $v) {
        $inRange = 0;
        $lastIn = 0;
        foreach ((array) ($v['visit_log'] ?? []) as $iso) {
            $ts = (int) strtotime((string) $iso);
            if ($ts >= $fromTs && $ts <= $toTs) {
                $inRange++;
                $lastIn = max($lastIn, $ts);
            }
        }
        if ($inRange === 0) {
            continue;
        }
        $unique++;
        $visitsTotal += $inRange;
        // Повторный в периоде: уже заходил раньше периода либо зашёл в нём больше одного раза
        if ((int) strtotime((string) ($v['first_seen'] ?? '')) < $fromTs || $inRange >= 2) {
            $repeat++;
        }
        $clicks = (array) ($v['clicks'] ?? []);
        foreach ($clicks as $btn => $c) {
            $clicksTotal[$btn] = ($clicksTotal[$btn] ?? 0) + (int) $c;
        }
        $fs = (array) ($v['first_source'] ?? []);
        $rows[] = [
            'id'           => (string) $id,
            'visits'       => (int) ($v['visits'] ?? 0),
            'visitsInRange' => $inRange,
            'firstSeen'    => $local((int) strtotime((string) ($v['first_seen'] ?? ''))),
            'lastSeen'     => $local((int) strtotime((string) ($v['last_seen'] ?? ''))),
            'lastIn'       => $lastIn,
            'firstSource'  => [
                'type'     => (string) ($fs['type'] ?? 'direct'),
                'domain'   => (string) ($fs['domain'] ?? ''),
                'campaign' => (string) ($fs['utm_campaign'] ?? ''),
                'source'   => (string) ($fs['utm_source'] ?? ''),
            ],
            'device'       => (array) ($v['device'] ?? []),
            'clicks'       => $clicks,
        ];
    }
    usort($rows, static fn($a, $b) => $b['lastIn'] <=> $a['lastIn']);
    $totalRows = count($rows);
    $rows = array_map(static function (array $r): array {
        unset($r['lastIn']);
        return $r;
    }, array_slice($rows, max(0, $offset), max(1, min(500, $limit))));
    arsort($clicksTotal);

    // --- ссылки и источники ---
    $types = array_fill_keys(KD_SOURCE_TYPES, 0);
    $domains = [];
    $urls = [];
    $utm = [];
    foreach (stats_links_store()->read()['days'] as $day => $row) {
        if ((string) $day < $from || (string) $day > $to) {
            continue;
        }
        foreach ((array) ($row['types'] ?? []) as $t => $n) {
            if (isset($types[$t])) {
                $types[$t] += (int) $n;
            }
        }
        foreach ((array) ($row['domains'] ?? []) as $dom => $x) {
            $domains[$dom] = ['domain' => (string) $dom, 'type' => (string) ($x['type'] ?? 'other'), 'n' => ($domains[$dom]['n'] ?? 0) + (int) ($x['n'] ?? 0)];
        }
        foreach ((array) ($row['urls'] ?? []) as $url => $x) {
            $urls[$url] = ['url' => (string) $url, 'domain' => (string) ($x['domain'] ?? ''), 'type' => (string) ($x['type'] ?? 'other'), 'n' => ($urls[$url]['n'] ?? 0) + (int) ($x['n'] ?? 0)];
        }
        foreach ((array) ($row['utm'] ?? []) as $key => $n) {
            $utm[$key] = ($utm[$key] ?? 0) + (int) $n;
        }
    }
    $byN = static fn($a, $b) => $b['n'] <=> $a['n'];
    $domains = array_values($domains);
    usort($domains, $byN);
    $urls = array_values($urls);
    usort($urls, $byN);
    arsort($utm);
    $utmRows = [];
    foreach ($utm as $key => $n) {
        [$s, $m, $c] = array_pad(explode('|', (string) $key, 3), 3, '');
        $utmRows[] = ['source' => $s, 'medium' => $m, 'campaign' => $c, 'n' => $n];
    }

    return [
        'range'    => ['from' => $from, 'to' => $to, 'tz' => KD_STATS_TZ],
        'summary'  => [
            'unique'      => $unique,
            'visits'      => $visitsTotal,
            'repeat'      => $repeat,
            'repeatShare' => $unique > 0 ? round($repeat * 100 / $unique, 1) : 0.0,
            'clicks'      => array_sum($clicksTotal),
        ],
        'time'     => [
            'hours'    => $hours,
            'weekdays' => $weekdays,
            'months'   => array_map(static fn($k, $n) => ['key' => $k, 'n' => $n], array_keys($months), array_values($months)),
        ],
        'links'    => [
            'types'   => $types,
            'domains' => array_slice($domains, 0, 40),
            'urls'    => array_slice($urls, 0, 60),
            'utm'     => array_slice($utmRows, 0, 60),
        ],
        'clicks'   => $clicksTotal,
        'visitors' => ['total' => $totalRows, 'offset' => max(0, $offset), 'rows' => $rows],
    ];
}
