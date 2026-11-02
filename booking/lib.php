<?php
/**
 * КЛЁВАЯ ДАЧА — бронирование. Общая библиотека серверной части.
 *
 * Подключается из api.php (публичные операции), admin-api.php (операции хозяина)
 * и admin.php (страница управления). Напрямую из браузера не открывается.
 *
 * Содержание:
 *   1. Ошибки, конфигурация, запуск
 *   2. Ответы сервера (единый формат JSON)
 *   3. Даты, очистка и проверка ввода, телефон
 *   4. Хранилище JSON: flock + lock-файл + атомарная запись + резервные копии
 *   5. Бронирования: пересечения периодов
 *   6. Определение устройства
 *   7. Telegram
 *   8. Защита формы (токен, ограничение частоты) и доступ хозяина
 */

declare(strict_types=1);

if (!defined('KD_BOOKING')) {
    http_response_code(403);
    exit;
}

/* ====================================================================
   1. ОШИБКИ, КОНФИГУРАЦИЯ, ЗАПУСК
   ==================================================================== */

/** Ожидаемая ошибка: понятное сообщение для посетителя или хозяина. */
final class ApiError extends Exception
{
    public function __construct(
        public readonly string $errCode,
        string $message,
        public readonly int $status = 400,
        public readonly array $extra = []
    ) {
        parent::__construct($message);
    }
}

/** Ошибка чтения или записи JSON-файла. Подробности — только в журнал. */
final class StorageError extends RuntimeException
{
}

const KD_STATUSES = ['new', 'contacted', 'confirmed', 'rejected', 'cancelled'];
const KD_STATUS_LABELS = [
    'new'       => 'Новая заявка',
    'contacted' => 'Хозяин связался',
    'confirmed' => 'Бронирование подтверждено',
    'rejected'  => 'Заявка отклонена',
    'cancelled' => 'Бронирование отменено',
];
const KD_JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

function kd_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $file = __DIR__ . '/config.php';
        if (!is_file($file)) {
            throw new RuntimeException('Не найден booking/config.php');
        }
        $loaded = require $file;
        if (!is_array($loaded)) {
            throw new RuntimeException('booking/config.php должен возвращать массив');
        }
        $cfg = $loaded;
    }
    return $cfg;
}

function cfg(string $key, mixed $default = null): mixed
{
    $c = kd_config();
    return array_key_exists($key, $c) ? $c[$key] : $default;
}

function data_dir(): string
{
    return rtrim((string) cfg('data_dir', __DIR__ . '/data'), '/\\');
}

/** Настройки PHP, часовой пояс, перехват предупреждений. Вызывать в начале каждого обработчика. */
function kd_bootstrap(): void
{
    ini_set('display_errors', '0');          // предупреждения не должны попадать в JSON
    error_reporting(E_ALL);
    mb_internal_encoding('UTF-8');
    date_default_timezone_set((string) cfg('timezone', 'Europe/Astrakhan'));

    set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
        if (!(error_reporting() & $no)) {
            return false;                    // подавлено оператором @
        }
        log_line('warning', "PHP[$no] $str @ " . basename($file) . ":$line");
        return true;
    });
}

/** Журнал для диагностики: data/booking.log (секреты маскируются, телефоны не пишутся). */
function log_line(string $level, string $message): void
{
    try {
        foreach (['telegram_bot_token', 'admin_key', 'form_secret'] as $k) {
            $secret = (string) cfg($k, '');
            if ($secret !== '') {
                $message = str_replace($secret, '***', $message);
            }
        }
        $dir = data_dir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $file = $dir . '/booking.log';
        if (is_file($file) && (int) @filesize($file) > 1048576) {
            @rename($file, $file . '.1');
        }
        $line = date('Y-m-d H:i:s') . ' [' . $level . '] ' . str_replace(["\r", "\n"], ' ', $message) . "\n";
        if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log('kd-booking: ' . $message);
        }
    } catch (Throwable $e) {
        error_log('kd-booking: ' . $message);
    }
}

/**
 * Единая обёртка обработчика: любая ошибка превращается в JSON нужного вида.
 * $admin = true — хозяину показываем причину сбоя хранилища, посетителю — нет.
 */
function kd_run(callable $fn, bool $admin = false): void
{
    try {
        kd_bootstrap();
        $fn();
    } catch (ApiError $e) {
        fail($e->errCode, $e->getMessage(), $e->status, $e->extra);
    } catch (StorageError $e) {
        log_line('error', 'Хранилище: ' . $e->getMessage());
        if ($admin) {
            fail('storage_error', 'Ошибка хранилища данных: ' . $e->getMessage() . ' Подробности — в data/booking.log.', 500);
        }
        fail('storage_error', 'Сервис бронирования временно недоступен. Попробуйте позже или позвоните хозяину: ' . owner_phone() . '.', 503);
    } catch (Throwable $e) {
        log_line('error', get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        fail('server_error', 'Внутренняя ошибка сервера. Попробуйте позже.', 500);
    }
}

function owner_phone(): string
{
    return (string) cfg('owner_phone', '+7 917 172-55-58');
}

/* ====================================================================
   2. ОТВЕТЫ СЕРВЕРА
   Успех:  {"ok": true,  "data": {...}}
   Ошибка: {"ok": false, "error": {"code": "...", "message": "...", ...}}
   ==================================================================== */

const KD_OUT_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

function send_json_headers(int $status): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow');
}

function json_out(array $payload, int $status = 200): never
{
    send_json_headers($status);
    echo json_encode($payload, KD_OUT_FLAGS);
    exit;
}

function ok(array $data = [], int $status = 200): never
{
    json_out(['ok' => true, 'data' => $data === [] ? new stdClass() : $data], $status);
}

function fail(string $code, string $message, int $status = 400, array $extra = []): never
{
    json_out(['ok' => false, 'error' => array_merge(['code' => $code, 'message' => $message], $extra)], $status);
}

/**
 * Сначала отдаём ответ посетителю, потом выполняем долгую работу (отправку в Telegram),
 * чтобы медленный или недоступный Telegram не заставлял человека ждать.
 */
function ok_then(array $data, callable $after): never
{
    $body = json_encode(['ok' => true, 'data' => $data === [] ? new stdClass() : $data], KD_OUT_FLAGS);
    ignore_user_abort(true);
    @set_time_limit(60);
    send_json_headers(200);
    header('Content-Length: ' . strlen((string) $body));
    header('Connection: close');
    echo $body;
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        flush();
    }
    try {
        $after();
    } catch (Throwable $e) {
        log_line('error', 'После ответа: ' . get_class($e) . ': ' . $e->getMessage());
    }
    exit;
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        fail('method_not_allowed', 'Недопустимый метод запроса.', 405);
    }
}

function read_json_body(int $maxBytes = 32768): array
{
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false || $raw === '') {
        throw new ApiError('bad_request', 'Пустой запрос.', 400);
    }
    if (strlen($raw) > $maxBytes) {
        throw new ApiError('too_large', 'Слишком большой запрос.', 413);
    }
    try {
        $data = json_decode($raw, true, 12, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new ApiError('bad_request', 'Некорректный формат запроса.', 400);
    }
    if (!is_array($data)) {
        throw new ApiError('bad_request', 'Некорректный формат запроса.', 400);
    }
    return $data;
}

/* ====================================================================
   3. ДАТЫ, ОЧИСТКА И ПРОВЕРКА ВВОДА, ТЕЛЕФОН
   Даты везде — строки YYYY-MM-DD, без часовых поясов.
   ==================================================================== */

function now_iso(): string
{
    return date('c');   // 2027-05-01T12:00:00+04:00
}

function today_iso(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone((string) cfg('timezone', 'Europe/Astrakhan'))))->format('Y-m-d');
}

function parse_iso_date(mixed $s): ?string
{
    if (!is_string($s) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
        return null;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $s : null;
}

function add_days(string $iso, int $n): string
{
    $d = new DateTimeImmutable($iso . ' 00:00:00', new DateTimeZone('UTC'));
    return $d->modify(($n >= 0 ? '+' : '') . $n . ' days')->format('Y-m-d');
}

/** Количество ночей между заездом и выездом (дата выезда не считается). */
function nights(string $in, string $out): int
{
    $utc = new DateTimeZone('UTC');
    $a = new DateTimeImmutable($in . ' 00:00:00', $utc);
    $b = new DateTimeImmutable($out . ' 00:00:00', $utc);
    return (int) $a->diff($b)->format('%r%a');
}

/**
 * Проверка периода. Возвращает [заезд, выезд, текст ошибки|null].
 * Публичные заявки: только будущие даты и ограничение по сроку.
 * Хозяин ($admin = true): можно и прошедшие даты (например, гость уже живёт).
 */
function check_range(mixed $in, mixed $out, bool $admin): array
{
    $a = parse_iso_date($in);
    $b = parse_iso_date($out);
    if ($a === null || $b === null) {
        return [null, null, 'Выберите даты заезда и выезда.'];
    }
    if ($b <= $a) {
        return [null, null, 'Дата выезда должна быть позже даты заезда: нужна хотя бы одна ночь.'];
    }
    $today = today_iso();
    $n = nights($a, $b);
    if ($admin) {
        if ($a < add_days($today, -366) || $a > add_days($today, 1100)) {
            return [null, null, 'Дата заезда вне допустимого диапазона.'];
        }
        if ($n > 366) {
            return [null, null, 'Период не может быть длиннее 366 ночей.'];
        }
    } else {
        if ($a < $today) {
            return [null, null, 'Нельзя выбрать прошедшую дату заезда.'];
        }
        if ($a > add_days($today, (int) cfg('booking_horizon_days', 730))) {
            return [null, null, 'Эта дата слишком далеко. Выберите более близкие даты.'];
        }
        $max = (int) cfg('max_nights', 60);
        if ($n > $max) {
            return [null, null, 'Онлайн-заявку можно оставить не более чем на ' . $max . ' ' . nights_word($max) . '. Для более длительного проживания позвоните хозяину.'];
        }
    }
    return [$a, $b, null];
}

/** Обрезка, удаление управляющих символов, схлопывание пробелов. */
function clean_text(mixed $v, int $max, bool $multiline = false): string
{
    if (!is_string($v)) {
        return '';
    }
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $v) ?? '';
    if ($multiline) {
        $v = str_replace(["\r\n", "\r"], "\n", $v);
        $v = preg_replace('/[ \t]+/u', ' ', $v) ?? '';
        $v = preg_replace('/\n{3,}/', "\n\n", $v) ?? '';
    } else {
        $v = preg_replace('/\s+/u', ' ', $v) ?? '';
    }
    return mb_substr(trim($v), 0, $max);
}

/** Проверка имени, фамилии или отчества. Ошибка записывается в $errors[$field]. */
function check_name(string $v, bool $required, string $field, string $genitive, array &$errors): string
{
    if ($v === '') {
        if ($required) {
            $errors[$field] = "Укажите $genitive.";
        }
        return '';
    }
    if (!preg_match('/^[\p{L}][\p{L}\p{M} .\'’-]{0,49}$/u', $v)) {
        $errors[$field] = 'Допустимы только буквы, пробел, дефис и апостроф.';
    }
    return $v;
}

/**
 * Приводит телефон к виду +7XXXXXXXXXX (или +код для иностранных номеров).
 * Возвращает null, если номер некорректен. Та же логика есть в JavaScript.
 */
function normalize_phone(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || mb_strlen($raw) > 30 || preg_match('/[^\d\s()+\-.]/u', $raw)) {
        return null;
    }
    $plus = str_starts_with($raw, '+');
    $d = preg_replace('/\D+/', '', $raw) ?? '';
    if ($d === '') {
        return null;
    }
    if ($plus) {
        if (strlen($d) < 8 || strlen($d) > 15) {
            return null;
        }
        if ($d[0] === '7' && strlen($d) !== 11) {
            return null;
        }
        $num = '+' . $d;
    } elseif (strlen($d) === 11 && ($d[0] === '8' || $d[0] === '7')) {
        $num = '+7' . substr($d, 1);
    } elseif (strlen($d) === 10 && preg_match('/^[3-9]/', $d)) {
        $num = '+7' . $d;
    } else {
        return null;
    }
    $digits = substr($num, 1);
    if (preg_match('/^(\d)\1+$/', $digits)) {
        return null;    // 00000000000, 77777777777 и т. п.
    }
    if (str_starts_with($num, '+7') && !preg_match('/^\+7[3-9]/', $num)) {
        return null;
    }
    return $num;
}

/** +79171234567 -> «+7 917 123-45-67» (для сообщений). Иностранные номера — как есть. */
function format_phone(string $num): string
{
    if (preg_match('/^\+7(\d{3})(\d{3})(\d{2})(\d{2})$/', $num, $m)) {
        return "+7 {$m[1]} {$m[2]}-{$m[3]}-{$m[4]}";
    }
    return $num;
}

/** 1 ночь, 2 ночи, 5 ночей. */
function nights_word(int $n): string
{
    $n = abs($n) % 100;
    $d = $n % 10;
    if ($n > 10 && $n < 20) {
        return 'ночей';
    }
    return $d === 1 ? 'ночь' : (($d >= 2 && $d <= 4) ? 'ночи' : 'ночей');
}

function format_date_ru(string $iso): string
{
    return substr($iso, 8, 2) . '.' . substr($iso, 5, 2) . '.' . substr($iso, 0, 4);
}

function new_id(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(8));
}

/* ====================================================================
   4. ХРАНИЛИЩЕ JSON
   - отдельный lock-файл: блокировка не теряется при замене основного файла;
   - чтение под LOCK_SH, изменение под LOCK_EX;
   - запись: временный файл + rename() (атомарно);
   - повреждённый JSON никогда не заменяется пустой структурой;
   - резервные копии в data/backups/.
   ==================================================================== */

/** Записывает файл целиком и атомарно: временный файл рядом, затем rename(). */
function atomic_write(string $path, string $content): void
{
    $dir = dirname($path);
    $tmp = @tempnam($dir, '.tmp-');
    if ($tmp === false) {
        throw new StorageError('Не удалось создать временный файл в каталоге данных.');
    }
    $h = @fopen($tmp, 'wb');
    if ($h === false) {
        @unlink($tmp);
        throw new StorageError('Не удалось открыть временный файл для записи.');
    }
    $len = strlen($content);
    $written = 0;
    while ($written < $len) {
        $n = @fwrite($h, substr($content, $written));
        if ($n === false || $n === 0) {
            fclose($h);
            @unlink($tmp);
            throw new StorageError('Не удалось записать данные (возможно, нет места на диске).');
        }
        $written += $n;
    }
    fflush($h);
    if (function_exists('fsync')) {
        @fsync($h);
    }
    fclose($h);
    clearstatcache(true, $tmp);
    if ((int) @filesize($tmp) !== $len) {
        @unlink($tmp);
        throw new StorageError('Размер записанного файла не совпал с ожидаемым.');
    }
    @chmod($tmp, 0640);
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        throw new StorageError('Не удалось заменить файл данных.');
    }
}

final class Store
{
    private string $dir;
    private string $dataFile;
    private string $lockFile;

    public function __construct()
    {
        $this->dir      = data_dir();
        $this->dataFile = $this->dir . '/booking-data.json';
        $this->lockFile = $this->dir . '/booking-data.lock';
    }

    public static function emptyData(): array
    {
        return ['version' => 1, 'meta' => ['nextRequestNumber' => 1], 'requests' => [], 'bookings' => []];
    }

    /** Чтение: общая блокировка, чтобы не прочитать файл посреди изменения. */
    public function read(): array
    {
        $h = $this->lock(false);
        try {
            return $this->load();
        } finally {
            $this->unlock($h);
        }
    }

    /**
     * Единственный способ изменить данные. Под эксклюзивной блокировкой:
     * читает файл, вызывает $fn(&$data), и если данные изменились — делает
     * резервную копию (по политике) и атомарно записывает файл.
     * Если $fn бросает исключение, ничего не записывается.
     *
     * $opts['backup']: 'always' — копия перед каждой записью; 'hourly' — не чаще раза в час.
     */
    public function transaction(callable $fn, array $opts = []): mixed
    {
        $h = $this->lock(true);
        try {
            $data = $this->load();
            $before = json_encode($data, KD_JSON_FLAGS | JSON_THROW_ON_ERROR);
            $result = $fn($data);
            $after = json_encode($data, KD_JSON_FLAGS | JSON_THROW_ON_ERROR);
            if ($after !== $before || !is_file($this->dataFile)) {
                if (!empty($opts['backup'])) {
                    $this->backupLocked((string) $opts['backup']);
                }
                $pretty = json_encode($data, KD_JSON_FLAGS | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                atomic_write($this->dataFile, $pretty . "\n");
            }
            return $result;
        } finally {
            $this->unlock($h);
        }
    }

    /** Ручная резервная копия (кнопка в админ-панели). Возвращает имя файла. */
    public function backupNow(): string
    {
        $h = $this->lock(true);
        try {
            $this->load();   // убеждаемся, что копируем рабочий, а не повреждённый файл
            $name = $this->backupLocked('always');
            if ($name === '') {
                throw new StorageError('Не удалось создать резервную копию.');
            }
            return $name;
        } finally {
            $this->unlock($h);
        }
    }

    public function backupInfo(): array
    {
        $files = glob($this->dir . '/backups/booking-data-*.json') ?: [];
        rsort($files);
        return [
            'count'  => count($files),
            'latest' => $files ? date('c', (int) filemtime($files[0])) : null,
        ];
    }

    public function dataFileInfo(): array
    {
        return [
            'exists'   => is_file($this->dataFile),
            'size'     => is_file($this->dataFile) ? (int) filesize($this->dataFile) : 0,
            'writable' => is_dir($this->dir) && is_writable($this->dir),
        ];
    }

    /**
     * Ограничение частоты отправки заявок по адресу. Отдельный файл ratelimit.json,
     * тот же механизм блокировки. Возвращает false, если лимит исчерпан.
     */
    public function rateLimit(string $key, int $windowSec, int $maxInWindow, int $maxPerDay): bool
    {
        $h = $this->lock(true);
        try {
            $file = $this->dir . '/ratelimit.json';
            $all = [];
            if (is_file($file)) {
                $raw = @file_get_contents($file);
                $decoded = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($decoded)) {
                    $all = $decoded;
                }
            }
            $now = time();
            foreach ($all as $k => $times) {
                $kept = array_values(array_filter((array) $times, static fn($t) => is_int($t) && $t > $now - 86400));
                if ($kept) {
                    $all[$k] = $kept;
                } else {
                    unset($all[$k]);
                }
            }
            $mine = $all[$key] ?? [];
            $inWindow = count(array_filter($mine, static fn($t) => $t > $now - $windowSec));
            if ($inWindow >= $maxInWindow || count($mine) >= $maxPerDay) {
                return false;
            }
            $mine[] = $now;
            $all[$key] = $mine;
            atomic_write($file, json_encode($all, KD_JSON_FLAGS | JSON_THROW_ON_ERROR));
            return true;
        } finally {
            $this->unlock($h);
        }
    }

    /* ---------- внутреннее ---------- */

    /** @return resource */
    private function lock(bool $exclusive)
    {
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0750, true) && !is_dir($this->dir)) {
            throw new StorageError('Не удалось создать каталог данных.');
        }
        if (!is_writable($this->dir)) {
            throw new StorageError('Каталог данных недоступен для записи.');
        }
        $h = @fopen($this->lockFile, 'c+');
        if ($h === false) {
            throw new StorageError('Не удалось открыть lock-файл.');
        }
        $mode = ($exclusive ? LOCK_EX : LOCK_SH) | LOCK_NB;
        $deadline = microtime(true) + 10.0;
        while (!flock($h, $mode)) {
            if (microtime(true) > $deadline) {
                fclose($h);
                throw new StorageError('Не удалось получить блокировку за 10 секунд.');
            }
            usleep(40000);
        }
        return $h;
    }

    /** @param resource $h */
    private function unlock($h): void
    {
        flock($h, LOCK_UN);
        fclose($h);
    }

    private function load(): array
    {
        if (!is_file($this->dataFile)) {
            // Первый запуск — пустая структура. Но если рядом лежат резервные копии,
            // файл удалили по ошибке: не затираем историю, просим восстановить вручную.
            if (glob($this->dir . '/backups/booking-data-*.json')) {
                throw new StorageError('Файл booking-data.json отсутствует, но найдены резервные копии. Восстановите файл из data/backups/ вручную.');
            }
            return self::emptyData();
        }
        $raw = @file_get_contents($this->dataFile);
        if ($raw === false) {
            throw new StorageError('Не удалось прочитать booking-data.json.');
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);   // BOM, если файл правили в Блокноте
        }
        if (trim($raw) === '') {
            throw new StorageError('Файл booking-data.json пуст. Восстановите его из data/backups/.');
        }
        try {
            $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new StorageError('Файл booking-data.json повреждён (' . $e->getMessage() . '). Автоматическая замена отключена — восстановите файл из data/backups/.');
        }
        if (!is_array($data) || !isset($data['requests'], $data['bookings'])
            || !is_array($data['requests']) || !is_array($data['bookings'])) {
            throw new StorageError('Неверная структура booking-data.json: нет разделов requests и bookings.');
        }
        $data['version'] = $data['version'] ?? 1;
        if (!isset($data['meta']) || !is_array($data['meta'])) {
            $data['meta'] = [];
        }
        if (!isset($data['meta']['nextRequestNumber']) || !is_int($data['meta']['nextRequestNumber'])) {
            $max = 0;
            foreach ($data['requests'] as $r) {
                $max = max($max, (int) ($r['number'] ?? 0));
            }
            $data['meta']['nextRequestNumber'] = $max + 1;
        }
        return $data;
    }

    /** Копия текущего файла. Вызывать только под эксклюзивной блокировкой. Возвращает имя или ''. */
    private function backupLocked(string $policy): string
    {
        if (!is_file($this->dataFile)) {
            return '';
        }
        $bdir = $this->dir . '/backups';
        if (!is_dir($bdir)) {
            @mkdir($bdir, 0750, true);
        }
        if (!is_dir($bdir) || !is_writable($bdir)) {
            log_line('warning', 'Каталог резервных копий недоступен.');
            return '';
        }
        $files = glob($bdir . '/booking-data-*.json') ?: [];
        rsort($files);
        if ($policy === 'hourly' && $files && (time() - (int) filemtime($files[0])) < 3600) {
            return '';
        }
        $name = 'booking-data-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.json';
        if (!@copy($this->dataFile, $bdir . '/' . $name)) {
            log_line('warning', 'Не удалось создать резервную копию.');
            return '';
        }
        // Храним последние N копий
        $keep = max(5, (int) cfg('backup_keep', 60));
        $files = glob($bdir . '/booking-data-*.json') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
        return $name;
    }
}

/* ====================================================================
   5. БРОНИРОВАНИЯ: ПЕРЕСЕЧЕНИЯ
   Период A..B (заезд включается, выезд — нет) конфликтует с C..D, если A < D && B > C.
   ==================================================================== */

function ranges_overlap(string $a, string $b, string $c, string $d): bool
{
    return $a < $d && $b > $c;
}

function booking_is_active(array $b): bool
{
    return ($b['status'] ?? 'active') === 'active';
}

/** Активные бронирования, пересекающиеся с периодом. $excludeId — не считать само это бронирование. */
function find_conflicts(array $data, string $in, string $out, ?string $excludeId = null): array
{
    $res = [];
    foreach ($data['bookings'] as $b) {
        if (!booking_is_active($b)) {
            continue;
        }
        if ($excludeId !== null && ($b['id'] ?? '') === $excludeId) {
            continue;
        }
        if (ranges_overlap($in, $out, (string) $b['checkIn'], (string) $b['checkOut'])) {
            $res[] = $b;
        }
    }
    return $res;
}

/** Краткое описание конфликта для хозяина (без лишних данных). */
function conflict_view(array $b, array $data): array
{
    $num = null;
    if (!empty($b['requestId'])) {
        foreach ($data['requests'] as $r) {
            if ($r['id'] === $b['requestId']) {
                $num = $r['number'] ?? null;
                break;
            }
        }
    }
    return [
        'id'            => $b['id'],
        'checkIn'       => $b['checkIn'],
        'checkOut'      => $b['checkOut'],
        'source'        => $b['source'] ?? 'manual',
        'requestNumber' => $num,
        'note'          => $b['note'] ?? '',
    ];
}

function conflict_error(array $conflicts, array $data): ApiError
{
    return new ApiError(
        'dates_conflict',
        'Эти даты пересекаются с уже занятым периодом. Выберите другие даты или сначала измените существующую бронь.',
        409,
        ['conflicts' => array_map(static fn($b) => conflict_view($b, $data), $conflicts)]
    );
}

/* ====================================================================
   6. ОПРЕДЕЛЕНИЕ УСТРОЙСТВА
   ==================================================================== */

/** Тип устройства и «слепок» для админ-панели. Данные клиента — только как подсказка. */
function detect_device(array $client): array
{
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300);
    $ua = preg_replace('/[\x00-\x1F\x7F]/', '', $ua) ?? '';
    if (!mb_check_encoding($ua, 'UTF-8')) {
        $ua = '';
    }

    $type = 'pc';
    if (preg_match('/iPad|Tablet|PlayBook|Silk/i', $ua)
        || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)) {
        $type = 'tablet';
    } elseif (preg_match('/iPhone|iPod|Android|Mobile|Windows Phone|Opera Mini|IEMobile/i', $ua)) {
        $type = 'phone';
    }
    if ($type === 'pc' && stripos($ua, 'Macintosh') !== false && !empty($client['touch'])) {
        $type = 'tablet';   // iPad в режиме «версия для компьютера»
    }

    $os = '';
    if (preg_match('/Windows NT ([\d.]+)/', $ua, $m)) {
        $os = 'Windows ' . (['10.0' => '10/11', '6.3' => '8.1', '6.2' => '8', '6.1' => '7'][$m[1]] ?? $m[1]);
    } elseif (preg_match('/Android ([\d.]+)/', $ua, $m)) {
        $os = 'Android ' . $m[1];
    } elseif (preg_match('/(?:iPhone|CPU) OS ([\d_]+)/', $ua, $m)) {
        $os = 'iOS ' . str_replace('_', '.', $m[1]);
    } elseif (stripos($ua, 'Mac OS X') !== false) {
        $os = 'macOS';
    } elseif (stripos($ua, 'CrOS') !== false) {
        $os = 'ChromeOS';
    } elseif (stripos($ua, 'Linux') !== false) {
        $os = 'Linux';
    }

    $model = '';
    if (preg_match('/Android [\d.]+; ([^;)]{2,40})\)/', $ua, $m) && trim($m[1]) !== 'K') {
        $model = trim($m[1]);
    } elseif (stripos($ua, 'iPhone') !== false) {
        $model = 'iPhone';
    } elseif (stripos($ua, 'iPad') !== false) {
        $model = 'iPad';
    }

    $browser = '';
    if (preg_match('/YaBrowser\/(\d+)/', $ua, $m)) {
        $browser = 'Яндекс Браузер ' . $m[1];
    } elseif (preg_match('/(?:Edg|EdgA|EdgiOS)\/(\d+)/', $ua, $m)) {
        $browser = 'Edge ' . $m[1];
    } elseif (preg_match('/(?:OPR|OPT)\/(\d+)/', $ua, $m)) {
        $browser = 'Opera ' . $m[1];
    } elseif (preg_match('/SamsungBrowser\/(\d+)/', $ua, $m)) {
        $browser = 'Samsung Internet ' . $m[1];
    } elseif (preg_match('/(?:Firefox|FxiOS)\/(\d+)/', $ua, $m)) {
        $browser = 'Firefox ' . $m[1];
    } elseif (preg_match('/(?:Chrome|CriOS)\/(\d+)/', $ua, $m)) {
        $browser = 'Chrome ' . $m[1];
    } elseif (preg_match('/Version\/(\d+).*Safari/', $ua, $m)) {
        $browser = 'Safari ' . $m[1];
    }

    $page = (($client['page'] ?? '') === 'mobile') ? 'mobile' : 'desktop';
    $screen = (isset($client['screen']) && is_string($client['screen']) && preg_match('/^\d{2,5}x\d{2,5}$/', $client['screen']))
        ? $client['screen'] : '';
    $lang = (isset($client['lang']) && is_string($client['lang']) && preg_match('/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})?$/', $client['lang']))
        ? $client['lang'] : '';
    $tz = (isset($client['tz']) && is_string($client['tz']) && preg_match('/^[A-Za-z0-9_\/+-]{1,40}$/', $client['tz']))
        ? $client['tz'] : '';

    $parts = array_filter([
        $os,
        $model,
        $browser,
        $screen !== '' ? 'экран ' . str_replace('x', '×', $screen) : '',
        $page === 'mobile' ? 'мобильная версия сайта' : 'полная версия сайта',
    ]);

    return [
        'type'      => $type,
        'page'      => $page,
        'label'     => implode(', ', $parts),
        'screen'    => $screen,
        'lang'      => $lang,
        'timezone'  => $tz,
        'userAgent' => $ua,
    ];
}

const KD_DEVICE_LABELS = ['phone' => 'телефон', 'tablet' => 'планшет', 'pc' => 'компьютер'];
const KD_DEVICE_GENITIVE = ['phone' => 'телефона', 'tablet' => 'планшета', 'pc' => 'компьютера'];

/* ====================================================================
   7. TELEGRAM
   Bot API без сторонних библиотек: cURL, а если его нет — потоки PHP.
   Токен берётся только из config.php и нигде не выводится.
   ==================================================================== */

function telegram_configured(): bool
{
    return (string) cfg('telegram_bot_token', '') !== '' && (string) cfg('telegram_chat_id', '') !== '';
}

/** @return array{ok: bool, error: string} */
function telegram_api(string $method, array $params): array
{
    $token = (string) cfg('telegram_bot_token', '');
    $chat  = (string) cfg('telegram_chat_id', '');
    if ($token === '' || $chat === '') {
        return ['ok' => false, 'error' => 'not_configured'];
    }
    // Если api.telegram.org недоступен с хостинга, запросы идут через посредника (Cloudflare Worker).
    $proxy       = rtrim((string) cfg('telegram_proxy_url', ''), '/');
    $proxySecret = (string) cfg('telegram_proxy_secret', '');
    $base        = $proxy !== '' ? $proxy : 'https://api.telegram.org';
    $extraHeader = ($proxy !== '' && $proxySecret !== '') ? 'X-Proxy-Secret: ' . $proxySecret : '';
    $url = $base . '/bot' . $token . '/' . $method;
    $body = http_build_query($params);
    $response = false;
    $transportError = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($extraHeader !== '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [$extraHeader]);
        }
        $response = curl_exec($ch);
        if ($response === false) {
            $transportError = 'curl: ' . curl_error($ch);
        }
    } elseif ((bool) ini_get('allow_url_fopen')) {
        $context = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/x-www-form-urlencoded\r\n" . ($extraHeader !== '' ? $extraHeader . "\r\n" : ''),
            'content'       => $body,
            'timeout'       => 10,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $transportError = $proxy !== '' ? 'Не удалось соединиться с посредником Telegram' : 'Не удалось соединиться с api.telegram.org';
        }
    } else {
        return ['ok' => false, 'error' => 'На хостинге недоступны ни cURL, ни allow_url_fopen.'];
    }

    if ($response === false) {
        return ['ok' => false, 'error' => mask_token(mask_token($transportError, $token), $proxySecret)];
    }
    $json = json_decode((string) $response, true);
    if (is_array($json) && ($json['ok'] ?? false) === true) {
        return ['ok' => true, 'error' => ''];
    }
    $code = is_array($json) ? (int) ($json['error_code'] ?? 0) : 0;
    $desc = is_array($json) ? (string) ($json['description'] ?? '') : 'неожиданный ответ';
    $hint = match ($code) {
        401 => 'Неверный токен бота.',
        400 => 'Telegram отклонил запрос (проверьте Chat ID): ' . $desc,
        403 => 'Бот не может писать этому чату: откройте бота в Telegram и нажмите «Start».',
        429 => 'Telegram просит подождать, слишком много запросов.',
        default => $desc,
    };
    return ['ok' => false, 'error' => mask_token('telegram ' . $code . ': ' . $hint, $token)];
}

function mask_token(string $s, string $token): string
{
    return $token !== '' ? str_replace($token, '***', $s) : $s;
}

/** @return array{ok: bool, error: string} */
function telegram_send(string $text): array
{
    return telegram_api('sendMessage', [
        'chat_id'                  => (string) cfg('telegram_chat_id', ''),
        'text'                     => $text,
        'disable_web_page_preview' => 'true',
    ]);
}

function build_telegram_text(array $r): string
{
    $dev = $r['device'] ?? [];
    $fromWhat = KD_DEVICE_GENITIVE[$dev['type'] ?? 'pc'] ?? 'компьютера';
    $n = nights((string) $r['checkIn'], (string) $r['checkOut']);
    $or = static fn(string $v, string $empty = 'не указано') => $v !== '' ? $v : $empty;

    return "Новая заявка на бронирование № {$r['number']}\n"
        . 'Имя: ' . $or((string) $r['firstName']) . "\n"
        . 'Фамилия: ' . $or((string) $r['lastName']) . "\n"
        . 'Отчество: ' . $or((string) $r['patronymic']) . "\n"
        . 'Телефон: ' . format_phone((string) $r['phone']) . "\n"
        . 'Заезд: ' . format_date_ru((string) $r['checkIn']) . "\n"
        . 'Выезд: ' . format_date_ru((string) $r['checkOut']) . "\n"
        . 'Количество ночей: ' . $n . "\n"
        . 'Статус: ' . KD_STATUS_LABELS['new'] . "\n"
        . 'Комментарий: ' . $or((string) ($r['comment'] ?? ''), 'не указан') . "\n"
        . 'Заявка оставлена с: ' . $fromWhat . ".\n\n"
        . 'Свяжитесь с клиентом для уточнения бронирования.';
}

/**
 * Отправляет уведомление по сохранённой заявке и записывает результат в JSON.
 * Блокировка файла на время HTTP-запроса НЕ удерживается: сначала читаем,
 * потом отправляем, потом коротко блокируем для записи результата.
 *
 * @return array{ok: bool, error: string}
 */
function notify_request(string $id): array
{
    $store = new Store();
    $data = $store->read();
    $req = null;
    foreach ($data['requests'] as $r) {
        if ($r['id'] === $id) {
            $req = $r;
            break;
        }
    }
    if ($req === null) {
        return ['ok' => false, 'error' => 'Заявка не найдена.'];
    }

    $res = telegram_send(build_telegram_text($req));

    try {
        $store->transaction(static function (array &$d) use ($id, $res): void {
            foreach ($d['requests'] as &$r) {
                if ($r['id'] !== $id) {
                    continue;
                }
                $r['notificationAttempts'] = (int) ($r['notificationAttempts'] ?? 0) + 1;
                $r['lastNotificationAt'] = now_iso();
                if ($res['ok']) {
                    $r['notificationStatus'] = 'sent';
                    $r['notificationError'] = '';
                    $r['notifiedAt'] = now_iso();
                } else {
                    $r['notificationStatus'] = $res['error'] === 'not_configured' ? 'not_configured' : 'failed';
                    $r['notificationError'] = $res['error'];
                }
                break;
            }
            unset($r);
        });
    } catch (StorageError $e) {
        log_line('error', 'Не удалось записать результат уведомления: ' . $e->getMessage());
    }

    log_line($res['ok'] ? 'info' : 'warning', 'Telegram, заявка №' . $req['number'] . ': ' . ($res['ok'] ? 'отправлено' : $res['error']));
    return $res;
}

/* ====================================================================
   8. ЗАЩИТА ФОРМЫ И ДОСТУП ХОЗЯИНА
   ==================================================================== */

function form_secret(): string
{
    $s = (string) cfg('form_secret', '');
    if ($s !== '') {
        return $s;
    }
    return hash('sha256', (string) cfg('admin_key', '') . '|' . (string) cfg('telegram_bot_token', '') . '|' . __DIR__);
}

/** Подписанная метка времени: выдаётся вместе с календарём, проверяется при отправке заявки. */
function make_form_token(): string
{
    $ts = (string) time();
    return $ts . '.' . substr(hash_hmac('sha256', 'form|' . $ts, form_secret()), 0, 32);
}

/** @return string 'ok' | 'invalid' | 'expired' | 'too_fast' */
function check_form_token(string $token): string
{
    if (!preg_match('/^(\d{9,11})\.([0-9a-f]{32})$/', $token, $m)) {
        return 'invalid';
    }
    $expected = substr(hash_hmac('sha256', 'form|' . $m[1], form_secret()), 0, 32);
    if (!hash_equals($expected, $m[2])) {
        return 'invalid';
    }
    $age = time() - (int) $m[1];
    if ($age > 86400) {
        return 'expired';
    }
    if ($age < (int) cfg('min_fill_seconds', 2)) {
        return 'too_fast';
    }
    return 'ok';
}

function client_ip_key(): string
{
    $ip = '';
    $header = (string) cfg('ip_header', '');
    if ($header !== '' && !empty($_SERVER[$header])) {
        $ip = trim(explode(',', (string) $_SERVER[$header])[0]);
    }
    if ($ip === '') {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
    return substr(hash_hmac('sha256', $ip, form_secret()), 0, 20);   // сам адрес не храним
}

/** Ключ хозяина задан и достаточно длинный. */
function admin_key_valid_format(): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_-]{24,128}$/', (string) cfg('admin_key', ''));
}

function admin_key_matches(string $given): bool
{
    if (!admin_key_valid_format()) {
        return false;
    }
    return hash_equals((string) cfg('admin_key', ''), $given);
}