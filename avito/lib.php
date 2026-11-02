<?php
declare(strict_types=1);

// Предупреждения PHP не должны попадать в вывод (и ломать JSON). Они пишутся в лог хостинга.
ini_set('display_errors', '0');

// ВАЖНО: секрет хранится только здесь, на сервере. В JS его нельзя.
const AVITO_CLIENT_ID     = 'ПОДСТАВИТЬ_СВОЙ';
const AVITO_CLIENT_SECRET = 'ПОДСТАВИТЬ_СВОЙ';//ВСТАВЬ_СЮДА_НОВЫЙ_СЕКРЕТ
const AVITO_STATE         = 'ПОДСТАВИТЬ_СВОЙ'; // тот же state ставишь в ссылке для хозяина
const AVITO_API           = 'ПОДСТАВИТЬ_СВОЙ';

const TOKEN_FILE = __DIR__ . '/tokens.json';
const CACHE_FILE = __DIR__ . '/reviews_cache.json';
const CACHE_TTL  = 3600; // секунд: отзывы с Авито запрашиваем не чаще раза в час

function http_request(string $method, string $url, array $headers = [], ?array $form = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($form !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $body = curl_exec($ch);
    if ($body === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("cURL: $err");
    }
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        throw new RuntimeException("Avito HTTP $code: $body");
    }
    $json = json_decode($body, true);
    return is_array($json) ? $json : [];
}

function check_config(): void
{
    if (!preg_match('/^[A-Za-z0-9_-]{20,}$/', AVITO_CLIENT_SECRET)) {
        throw new RuntimeException('В lib.php не заполнен AVITO_CLIENT_SECRET (там должна быть настоящая строка из латиницы и цифр, без пробелов и кавычек внутри).');
    }
}

function save_tokens(array $t): void
{
    if (empty($t['access_token'])) {
        // Авито ответил, но без токена: чаще всего это сообщение об ошибке
        throw new RuntimeException('Авито не вернул access_token. Ответ: ' . json_encode($t, JSON_UNESCAPED_UNICODE));
    }
    $old = is_file(TOKEN_FILE) ? (json_decode(file_get_contents(TOKEN_FILE), true) ?: []) : [];
    $data = [
        'access_token'  => $t['access_token'],
        // если Авито прислал новый refresh_token — берём его, иначе оставляем старый
        'refresh_token' => $t['refresh_token'] ?? ($old['refresh_token'] ?? ''),
        'expires_at'    => time() + (int)($t['expires_in'] ?? 3600) - 60,
    ];
    file_put_contents(TOKEN_FILE, json_encode($data), LOCK_EX);
    @chmod(TOKEN_FILE, 0600);
}

// Шаг 1 (один раз): меняем одноразовый code на токены
function exchange_code(string $code): void
{
    check_config();
    save_tokens(http_request('POST', AVITO_API . '/token', [], [
        'grant_type'    => 'authorization_code',
        'client_id'     => AVITO_CLIENT_ID,
        'client_secret' => AVITO_CLIENT_SECRET,
        'code'          => $code,
    ]));
}

// Дальше: берём access_token, при необходимости обновляем через refresh_token
function get_access_token(): string
{
    if (!is_file(TOKEN_FILE)) {
        throw new RuntimeException('Нет tokens.json: сначала пройди авторизацию через avito_callback.php');
    }
    $t = json_decode(file_get_contents(TOKEN_FILE), true);
    if (($t['expires_at'] ?? 0) > time()) {
        return $t['access_token'];
    }
    check_config();
    save_tokens(http_request('POST', AVITO_API . '/token', [], [
        'grant_type'    => 'refresh_token',
        'client_id'     => AVITO_CLIENT_ID,
        'client_secret' => AVITO_CLIENT_SECRET,
        'refresh_token' => $t['refresh_token'],
    ]));
    $t = json_decode(file_get_contents(TOKEN_FILE), true);
    return $t['access_token'];
}

function avito_get(string $path, array $query = []): array
{
    $url = AVITO_API . $path . ($query ? '?' . http_build_query($query) : '');
    return http_request('GET', $url, ['Authorization: Bearer ' . get_access_token()]);
}

// Возвращает ['rating' => ..., 'total' => N, 'reviews' => [...]] или null, если данных нет совсем.
// Сначала смотрит в кэш (живёт CACHE_TTL секунд); если кэш устарел, идёт на Авито;
// если Авито недоступен, отдаёт устаревший кэш.
function get_reviews_data(): ?array
{
    $cache = is_file(CACHE_FILE) ? json_decode(file_get_contents(CACHE_FILE), true) : null;
    if (($cache['v'] ?? 0) !== 3) {
        $cache = null; // кэш старого формата игнорируем
    }
    if ($cache && time() - ($cache['saved_at'] ?? 0) < CACHE_TTL) {
        return $cache['data'];
    }

    try {
        $raw    = [];
        $offset = 0;
        $limit  = 50; // максимум для этого метода
        $total  = 0;

        do {
            $page   = avito_get('/ratings/v1/reviews', ['offset' => $offset, 'limit' => $limit]);
            $total  = (int)($page['total'] ?? 0);
            $chunk  = $page['reviews'] ?? [];
            $raw    = array_merge($raw, $chunk);
            $offset += $limit;
        } while ($chunk && count($raw) < $total && $offset < 200);

        // Оставляем только нужные поля (без картинок и прочего)
        $reviews = [];
        foreach ($raw as $r) {
            // Фото: у каждого есть несколько размеров. Берём маленький (превью) и большой (для просмотра).
            $images = [];
            foreach (($r['images'] ?? []) as $img) {
                $bySize = [];
                foreach (($img['sizes'] ?? []) as $sz) {
                    if (!empty($sz['size']) && !empty($sz['url'])) {
                        $bySize[$sz['size']] = $sz['url'];
                    }
                }
                if (!$bySize) {
                    continue;
                }
                $images[] = [
                    'thumb' => $bySize['256x192'] ?? $bySize['640x480'] ?? reset($bySize),
                    'full'  => $bySize['1280x960'] ?? $bySize['640x480'] ?? end($bySize),
                ];
            }

            $reviews[] = [
                'id'        => $r['id'] ?? null,
                'score'     => $r['score'] ?? null,
                'text'      => trim((string)($r['text'] ?? '')),
                'name'      => trim((string)($r['sender']['name'] ?? '')) ?: 'Гость',
                'createdAt' => (int)($r['createdAt'] ?? 0),
                'answer'    => $r['answer']['text'] ?? null,
                'images'    => $images,
            ];
        }
        usort($reviews, fn($a, $b) => $b['createdAt'] <=> $a['createdAt']); // новые сверху

        $rating = null;
        try {
            $rating = avito_get('/ratings/v1/info')['rating'] ?? null;
        } catch (Throwable $e) { /* рейтинг не критичен */ }

        $data = ['rating' => $rating, 'total' => $total, 'reviews' => $reviews];
        file_put_contents(
            CACHE_FILE,
            json_encode(['v' => 3, 'saved_at' => time(), 'data' => $data], JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        return $data;
    } catch (Throwable $e) {
        error_log('[avito] ' . $e->getMessage());
        return $cache['data'] ?? null;
    }
}