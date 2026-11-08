<?php
/**
 * КЛЁВАЯ ДАЧА — контент сайта: общее JSON-хранилище, отзывы с сайта, фото и видео главной страницы.
 *
 * Подключается после lib.php (из api.php, admin-api.php, track.php и public.php).
 * Здесь нет ничего, что отвечает на HTTP-запросы: только данные и правила.
 *
 *   1. JsonStore — то же надёжное хранение, что и Store в lib.php (flock + lock-файл +
 *      атомарная запись + резервные копии), но для любого JSON-файла.
 *   2. Отзывы с сайта: приём, модерация, выдача опубликованных.
 *   3. Фото и видео главной страницы: категории, порядок, размер, загрузка и удаление файлов.
 *   4. Разметка галереи и видео для index.php и mobile.php.
 */

declare(strict_types=1);

if (!defined('KD_BOOKING')) {
    http_response_code(403);
    exit;
}

/* ====================================================================
   1. УНИВЕРСАЛЬНОЕ JSON-ХРАНИЛИЩЕ
   ==================================================================== */

final class JsonStore
{
    private string $dir;
    private string $file;
    private string $lockFile;

    /**
     * @param string $name          имя без расширения: data/<name>.json и data/<name>.lock
     * @param array  $defaults      содержимое нового файла (и значения для недостающих разделов)
     * @param array  $requiredLists разделы, которые обязаны быть массивами (иначе файл считается повреждённым)
     */
    public function __construct(private string $name, private array $defaults, private array $requiredLists = [])
    {
        $this->dir      = data_dir();
        $this->file     = $this->dir . '/' . $name . '.json';
        $this->lockFile = $this->dir . '/' . $name . '.lock';
    }

    /** Чтение под общей блокировкой. */
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
     * Изменение данных: эксклюзивная блокировка, $fn(&$data), запись только если данные изменились.
     * Исключение внутри $fn отменяет запись. $opts['backup']: 'always' | 'hourly'.
     */
    public function transaction(callable $fn, array $opts = []): mixed
    {
        $h = $this->lock(true);
        try {
            $data = $this->load();
            $before = json_encode($data, KD_JSON_FLAGS | JSON_THROW_ON_ERROR);
            $result = $fn($data);
            $after = json_encode($data, KD_JSON_FLAGS | JSON_THROW_ON_ERROR);
            if ($after !== $before || !is_file($this->file)) {
                if (!empty($opts['backup'])) {
                    $this->backupLocked((string) $opts['backup']);
                }
                $pretty = json_encode($data, KD_JSON_FLAGS | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                atomic_write($this->file, $pretty . "\n");
            }
            return $result;
        } finally {
            $this->unlock($h);
        }
    }

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
            throw new StorageError('Не удалось открыть lock-файл ' . $this->name . '.');
        }
        $mode = ($exclusive ? LOCK_EX : LOCK_SH) | LOCK_NB;
        $deadline = microtime(true) + 10.0;
        while (!flock($h, $mode)) {
            if (microtime(true) > $deadline) {
                fclose($h);
                throw new StorageError('Не удалось получить блокировку ' . $this->name . ' за 10 секунд.');
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
        if (!is_file($this->file)) {
            if (glob($this->dir . '/backups/' . $this->name . '-*.json')) {
                throw new StorageError('Файл ' . $this->name . '.json отсутствует, но найдены резервные копии. Восстановите его из data/backups/ вручную.');
            }
            return $this->defaults;
        }
        $raw = @file_get_contents($this->file);
        if ($raw === false) {
            throw new StorageError('Не удалось прочитать ' . $this->name . '.json.');
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        if (trim($raw) === '') {
            throw new StorageError('Файл ' . $this->name . '.json пуст. Восстановите его из data/backups/.');
        }
        try {
            $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new StorageError('Файл ' . $this->name . '.json повреждён (' . $e->getMessage() . '). Автоматическая замена отключена.');
        }
        if (!is_array($data)) {
            throw new StorageError('Неверная структура ' . $this->name . '.json.');
        }
        foreach ($this->requiredLists as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                throw new StorageError('Неверная структура ' . $this->name . '.json: нет раздела ' . $key . '.');
            }
        }
        return $data + $this->defaults;   // недостающие верхнеуровневые разделы берём из значений по умолчанию
    }

    private function backupLocked(string $policy): void
    {
        if (!is_file($this->file)) {
            return;
        }
        $bdir = $this->dir . '/backups';
        if (!is_dir($bdir)) {
            @mkdir($bdir, 0750, true);
        }
        if (!is_dir($bdir) || !is_writable($bdir)) {
            return;
        }
        $files = glob($bdir . '/' . $this->name . '-*.json') ?: [];
        rsort($files);
        if ($policy === 'hourly' && $files && (time() - (int) filemtime($files[0])) < 3600) {
            return;
        }
        $copy = $bdir . '/' . $this->name . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.json';
        if (!@copy($this->file, $copy)) {
            log_line('warning', 'Не удалось создать резервную копию ' . $this->name . '.');
            return;
        }
        $files = glob($bdir . '/' . $this->name . '-*.json') ?: [];
        rsort($files);
        foreach (array_slice($files, max(5, (int) cfg('backup_keep', 60))) as $old) {
            @unlink($old);
        }
    }
}

/* ====================================================================
   2. ОТЗЫВЫ С САЙТА
   Жизненный цикл: pending (ждёт хозяина) -> published | rejected.
   createdAt — дата, когда отзыв написал посетитель; именно она показывается на сайте
   и по ней отзыв встаёт в общую ленту вместе с отзывами с Авито.
   ==================================================================== */

const KD_REVIEW_STATUSES = ['pending', 'published', 'rejected'];

function reviews_store(): JsonStore
{
    return new JsonStore('reviews-data', ['version' => 1, 'meta' => ['nextNumber' => 1], 'reviews' => [], 'pending' => []], ['reviews']);
}

/** «Иван Иванов» -> «Иван И.»: на сайте фамилию целиком не показываем. */
function review_display_name(string $first, string $last): string
{
    return $last !== '' ? $first . ' ' . mb_substr($last, 0, 1) . '.' : $first;
}

/**
 * Проверка полей формы отзыва. Возвращает [значения, ошибки по полям].
 * Телефон необязателен: он нужен хозяину, только если захочется уточнить детали, и на сайте не показывается.
 */
function review_validate(array $in): array
{
    $errors = [];
    $first = check_name(clean_text($in['firstName'] ?? '', 50), true, 'firstName', 'имя', $errors);
    $last  = check_name(clean_text($in['lastName'] ?? '', 50), false, 'lastName', 'фамилию', $errors);

    $phoneRaw = clean_text($in['phone'] ?? '', 30);
    $phone = '';
    if ($phoneRaw !== '') {
        $norm = normalize_phone($phoneRaw);
        if ($norm === null) {
            $errors['phone'] = 'Проверьте номер телефона или оставьте поле пустым.';
        } else {
            $phone = $norm;
        }
    }

    $text = clean_text($in['text'] ?? '', 2000, true);
    if (mb_strlen($text) < 10) {
        $errors['text'] = 'Напишите отзыв хотя бы в паре предложений (не короче 10 символов).';
    }
    if (($in['consent'] ?? false) !== true) {
        $errors['consent'] = 'Необходимо согласие на обработку персональных данных.';
    }
    return [['firstName' => $first, 'lastName' => $last, 'phone' => $phone, 'text' => $text], $errors];
}

/** Сообщение хозяину в Telegram: что за отзыв и что с ним можно сделать. */
function review_telegram_text(array $r): string
{
    $text = (string) $r['text'];
    if (mb_strlen($text) > 1500) {
        $text = mb_substr($text, 0, 1500) . '…';
    }
    $phone = (string) ($r['phone'] ?? '');
    $photos = 0;
    $videos = 0;
    foreach ((array) ($r['media'] ?? []) as $m) {
        if (($m['type'] ?? '') === 'video') {
            $videos++;
        } else {
            $photos++;
        }
    }
    $attach = $photos + $videos > 0
        ? 'Вложения: ' . ($photos ? $photos . ' фото' : '') . ($photos && $videos ? ', ' : '') . ($videos ? $videos . ' видео' : '') . "\n"
        : '';
    return "Новый отзыв с сайта № {$r['number']} ждёт публикации\n"
        . 'Имя: ' . trim($r['firstName'] . ' ' . ($r['lastName'] ?? '')) . "\n"
        . 'Телефон: ' . ($phone !== '' ? format_phone($phone) : 'не указан') . "\n"
        . 'Написан: ' . format_date_ru(substr((string) $r['createdAt'], 0, 10)) . ' в ' . substr((string) $r['createdAt'], 11, 5) . "\n"
        . $attach . "\n"
        . "Отзыв:\n" . $text . "\n\n"
        . 'Откройте админ-панель, вкладка «Отзывы»: там отзыв можно опубликовать, отредактировать перед публикацией или отклонить.';
}

/** Отправляет уведомление об отзыве и записывает результат (блокировка на время запроса не держится). */
function review_notify(string $id): array
{
    $store = reviews_store();
    $review = null;
    foreach ($store->read()['reviews'] as $r) {
        if (($r['id'] ?? '') === $id) {
            $review = $r;
            break;
        }
    }
    if ($review === null) {
        return ['ok' => false, 'error' => 'Отзыв не найден.'];
    }
    $res = telegram_send(review_telegram_text($review));
    try {
        $store->transaction(static function (array &$d) use ($id, $res): void {
            foreach ($d['reviews'] as &$r) {
                if (($r['id'] ?? '') !== $id) {
                    continue;
                }
                $r['notificationStatus'] = $res['ok'] ? 'sent' : ($res['error'] === 'not_configured' ? 'not_configured' : 'failed');
                $r['notificationError'] = $res['ok'] ? '' : $res['error'];
                break;
            }
            unset($r);
        });
    } catch (StorageError $e) {
        log_line('error', 'Результат уведомления об отзыве не записан: ' . $e->getMessage());
    }
    log_line($res['ok'] ? 'info' : 'warning', 'Telegram, отзыв №' . $review['number'] . ': ' . ($res['ok'] ? 'отправлено' : $res['error']));
    return $res;
}

/**
 * Опубликованные отзывы с сайта в том виде, в каком их ждёт render_reviews():
 * та же форма, что у отзывов с Авито, плюс source = 'site'. Любой сбой хранилища — пустой список.
 */
function kd_public_reviews(): array
{
    try {
        $data = reviews_store()->read();
    } catch (Throwable $e) {
        log_line('warning', 'Отзывы сайта не прочитаны: ' . $e->getMessage());
        return [];
    }
    $out = [];
    foreach ($data['reviews'] as $r) {
        if (($r['status'] ?? '') !== 'published' || trim((string) ($r['text'] ?? '')) === '') {
            continue;
        }
        $ts = (int) strtotime((string) ($r['createdAt'] ?? ''));
        if ($ts <= 0) {
            continue;
        }
        // Вложения посетителя: фото открываются в том же окне просмотра, что и фото с Авито
        $images = [];
        $videos = [];
        foreach ((array) ($r['media'] ?? []) as $m) {
            $file = (string) ($m['file'] ?? '');
            if ($file === '' || !is_file(dirname(__DIR__) . '/' . $file)) {
                continue;
            }
            if (($m['type'] ?? '') === 'video') {
                $videos[] = ['file' => $file];
            } else {
                $images[] = ['thumb' => (string) (($m['thumb'] ?? '') !== '' ? $m['thumb'] : $file), 'full' => $file];
            }
        }
        $out[] = [
            'id'        => (string) $r['id'],
            'score'     => null,
            'text'      => (string) $r['text'],
            'name'      => (string) ($r['displayName'] ?? $r['firstName'] ?? 'Гость'),
            'createdAt' => $ts,
            'answer'    => null,
            'images'    => $images,
            'videos'    => $videos,
            'source'    => 'site',
        ];
    }
    return $out;
}

/* ---------- вложения к отзывам: фото и видео посетителей ---------- */

const KD_REVIEW_MAX_PHOTOS = 6;
const KD_REVIEW_MAX_VIDEOS = 1;

/** Ограничения вложений (учитывают лимиты PHP на хостинге). Необязательные настройки config.php: max_review_photo_mb, max_review_video_mb. */
function review_media_limits(): array
{
    $cap = min(ini_bytes('post_max_size'), ini_bytes('upload_max_filesize'));
    $room = max(0, $cap - 262144);   // запас на служебные поля запроса
    return [
        'photos'     => KD_REVIEW_MAX_PHOTOS,
        'videos'     => KD_REVIEW_MAX_VIDEOS,
        'photoBytes' => (int) min((int) cfg('max_review_photo_mb', 10) * 1048576, $room),
        'videoBytes' => (int) min((int) cfg('max_review_video_mb', 50) * 1048576, $room),
    ];
}

/** Уменьшенная копия сохранённого JPEG для превью в отзыве. Возвращает относительный путь или ''. */
function media_make_thumb(string $rel, int $max = 480): string
{
    if (!media_limits()['gd'] || !str_ends_with($rel, '.jpg')) {
        return '';
    }
    $root = dirname(__DIR__);
    $src = @imagecreatefromjpeg($root . '/' . $rel);
    if ($src === false) {
        return '';
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1.0, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $thumb = substr($rel, 0, -4) . '_t.jpg';
    $ok = imagejpeg($dst, $root . '/' . $thumb, 78);
    imagedestroy($src);
    imagedestroy($dst);
    if (!$ok) {
        return '';
    }
    @chmod($root . '/' . $thumb, 0644);
    return $thumb;
}

/** Удаляет файлы вложений (оригинал и превью). */
function review_media_delete_files(array $media): void
{
    foreach ($media as $m) {
        media_delete_file((string) ($m['file'] ?? ''));
        media_delete_file((string) ($m['thumb'] ?? ''));
    }
}

/** Убирает из списка ожидающих вложения, которые не привязали к отзыву за сутки (и их файлы). */
function review_cleanup_pending(array &$data): void
{
    $keep = [];
    foreach ((array) ($data['pending'] ?? []) as $p) {
        if (time() - (int) ($p['createdAt'] ?? 0) > 86400) {
            review_media_delete_files([$p]);
        } else {
            $keep[] = $p;
        }
    }
    $data['pending'] = $keep;
}

/** Владелец загрузки: хеш токена формы. Привязать загруженное к отзыву может только тот, у кого тот же токен. */
function review_media_owner(string $token): string
{
    return substr(hash('sha256', 'rvm|' . $token), 0, 16);
}

/**
 * Принимает ОДИН файл (фото или видео) от посетителя, проверяет и временно сохраняет.
 * Фото перекодируются в JPEG (метаданные и любая «начинка» в файле пропадают), видео проверяются по
 * сигнатуре MP4/WebM. Файл ждёт привязки к отзыву до суток. Возвращает запись ожидающего вложения.
 */
function review_upload_file(array $f, string $owner): array
{
    if ((int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new ApiError('upload', upload_error_message((int) ($f['error'] ?? UPLOAD_ERR_NO_FILE)), 422);
    }
    $tmp = (string) ($f['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {
        throw new ApiError('upload', 'Некорректная загрузка.', 400);
    }
    $size = (int) ($f['size'] ?? 0);
    $lim = review_media_limits();
    $dir = media_ensure_dir('reviews');
    $base = bin2hex(random_bytes(8));
    $thumb = '';

    if (@getimagesize($tmp) !== false) {
        if ($size > $lim['photoBytes']) {
            throw new ApiError('too_large', 'Фото больше ' . (int) ($lim['photoBytes'] / 1048576) . ' МБ.', 413);
        }
        $saved = media_save_image($tmp, $dir, $base, 1600, 82);
        $type = 'photo';
        $file = $saved['file'];
        $thumb = media_make_thumb($file);
    } else {
        $head = (string) file_get_contents($tmp, false, null, 0, 12);
        $ext = null;
        if (strlen($head) >= 8 && substr($head, 4, 4) === 'ftyp') {
            $ext = 'mp4';
        } elseif (str_starts_with($head, "\x1A\x45\xDF\xA3")) {
            $ext = 'webm';
        }
        if ($ext === null) {
            throw new ApiError('bad_file', 'Подходят фото (JPG, PNG, WebP) и видео (MP4, WebM).', 422);
        }
        if ($size > $lim['videoBytes']) {
            throw new ApiError('too_large', 'Видео больше ' . (int) ($lim['videoBytes'] / 1048576) . ' МБ.', 413);
        }
        if (!@move_uploaded_file($tmp, $dir . '/' . $base . '.' . $ext)) {
            throw new ApiError('storage', 'Не удалось сохранить видео.', 500);
        }
        @chmod($dir . '/' . $base . '.' . $ext, 0644);
        $type = 'video';
        $file = 'uploads/reviews/' . $base . '.' . $ext;
    }

    $entry = ['id' => 'up_' . bin2hex(random_bytes(8)), 'type' => $type, 'file' => $file, 'thumb' => $thumb, 'owner' => $owner, 'createdAt' => time()];
    try {
        reviews_store()->transaction(static function (array &$data) use ($entry): void {
            review_cleanup_pending($data);
            $mine = count(array_filter($data['pending'], static fn($p) => ($p['owner'] ?? '') === $entry['owner']));
            if ($mine >= 12) {
                throw new ApiError('too_many', 'Слишком много загруженных файлов. Отправьте отзыв или повторите позже.', 429);
            }
            $data['pending'][] = $entry;
        });
    } catch (Throwable $e) {
        review_media_delete_files([$entry]);
        throw $e;
    }
    return $entry;
}

/** Привязывает загруженные вложения к отзыву (внутри транзакции). Возвращает список для записи в отзыв. */
function review_claim_media(array &$data, array $ids, string $owner): array
{
    $media = [];
    $photos = 0;
    $videos = 0;
    $pending = array_values((array) ($data['pending'] ?? []));
    foreach (array_unique($ids) as $id) {
        $index = null;
        foreach ($pending as $i => $p) {
            if (($p['id'] ?? '') === $id && ($p['owner'] ?? '') === $owner) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            throw new ApiError('media', 'Вложение устарело. Прикрепите файл заново.', 422);
        }
        $p = $pending[$index];
        ($p['type'] ?? '') === 'video' ? $videos++ : $photos++;
        if ($photos > KD_REVIEW_MAX_PHOTOS || $videos > KD_REVIEW_MAX_VIDEOS) {
            throw new ApiError('media', 'Можно прикрепить не больше ' . KD_REVIEW_MAX_PHOTOS . ' фото и ' . KD_REVIEW_MAX_VIDEOS . ' видео.', 422);
        }
        $media[] = ['type' => (string) $p['type'], 'file' => (string) $p['file'], 'thumb' => (string) ($p['thumb'] ?? '')];
        unset($pending[$index]);
    }
    $data['pending'] = array_values($pending);
    return $media;
}

/* ====================================================================
   3. ФОТО И ВИДЕО ГЛАВНОЙ СТРАНИЦЫ
   Файлы лежат в uploads/photos и uploads/videos (публичные, но без выполнения скриптов),
   описание — в data/content-data.json. Порядок показа = порядок в списке.
   ==================================================================== */

/** Фото в сетке: четверть ширины или половина (в CSS — small / large). */
const KD_PHOTO_SIZES = ['quarter', 'half'];

function content_defaults(): array
{
    return [
        'version'    => 1,
        'categories' => [
            ['id' => 'main', 'name' => 'Фото'],
            ['id' => 'visitors', 'name' => 'Фото от посетителей'],
        ],
        'photos'     => [],
        'videos'     => [],
    ];
}

function content_store(): JsonStore
{
    return new JsonStore('content-data', content_defaults(), ['categories', 'photos', 'videos']);
}

function uploads_dir(): string
{
    return dirname(__DIR__) . '/uploads';
}

/** Размер из php.ini («64M») в байтах. */
function ini_bytes(string $key): int
{
    $v = trim((string) ini_get($key));
    if ($v === '' || $v === '-1') {
        return PHP_INT_MAX;
    }
    $n = (int) $v;
    return match (strtolower(substr($v, -1))) {
        'g' => $n * 1024 ** 3,
        'm' => $n * 1024 ** 2,
        'k' => $n * 1024,
        default => $n,
    };
}

function media_limits(): array
{
    $post = ini_bytes('post_max_size');
    return [
        'gd'            => function_exists('imagecreatetruecolor') && function_exists('imagecreatefromjpeg'),
        'exif'          => function_exists('exif_read_data'),
        'uploadMax'     => ini_bytes('upload_max_filesize'),
        'postMax'       => $post,
        'maxPhotoBytes' => (int) cfg('max_photo_mb', 15) * 1048576,
        'maxVideoBytes' => (int) cfg('max_video_mb', 500) * 1048576,
        // Видео грузится кусками: кусок должен проходить и в post_max_size, и в память
        'chunkBytes'    => (int) max(262144, min(4194304, (int) floor(min($post, 8388608) * 0.7))),
    ];
}

/** Безопасно удаляет файл, но только внутри uploads/ (файлы, добавленные вручную в других папках, не трогаем). */
function media_delete_file(string $rel): void
{
    if ($rel === '' || !str_starts_with($rel, 'uploads/')) {
        return;
    }
    $root = realpath(uploads_dir());
    $path = realpath(dirname(__DIR__) . '/' . $rel);
    if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
        return;
    }
    @unlink($path);
}

function media_ensure_dir(string $sub): string
{
    $dir = uploads_dir() . '/' . $sub;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new ApiError('storage', 'Не удалось создать папку uploads/' . $sub . '. Проверьте права на запись.', 500);
    }
    if (!is_writable($dir)) {
        throw new ApiError('storage', 'Папка uploads/' . $sub . ' недоступна для записи.', 500);
    }
    return $dir;
}

/**
 * Проверяет и сохраняет картинку. С GD файл пересобирается заново (JPEG, не больше $maxSide px по длинной
 * стороне, с учётом поворота из EXIF): это убирает метаданные и любую «начинку» в файле. Без GD — сохраняется
 * как есть после проверки типа. Возвращает ['file' => 'uploads/…', 'w' => int, 'h' => int].
 */
function media_save_image(string $tmp, string $dir, string $baseName, int $maxSide = 1800, int $quality = 84): array
{
    $info = @getimagesize($tmp);
    if ($info === false) {
        throw new ApiError('bad_image', 'Файл не похож на изображение.', 422);
    }
    [$w, $h, $type] = $info;
    if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        throw new ApiError('bad_image', 'Подходят только JPG, PNG и WebP.', 422);
    }
    if ($w < 1 || $h < 1 || $w * $h > 40000000) {
        throw new ApiError('bad_image', 'Слишком большое разрешение (больше 40 мегапикселей).', 422);
    }
    $rel = 'uploads/' . basename($dir);

    if (!media_limits()['gd']) {
        $ext = $type === IMAGETYPE_JPEG ? 'jpg' : ($type === IMAGETYPE_PNG ? 'png' : 'webp');
        $dest = $dir . '/' . $baseName . '.' . $ext;
        if (!@copy($tmp, $dest)) {
            throw new ApiError('storage', 'Не удалось сохранить файл.', 500);
        }
        @chmod($dest, 0644);
        return ['file' => $rel . '/' . $baseName . '.' . $ext, 'w' => $w, 'h' => $h];
    }

    @ini_set('memory_limit', '384M');
    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
        IMAGETYPE_PNG  => @imagecreatefrompng($tmp),
        default        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
    };
    if ($src === false) {
        throw new ApiError('bad_image', 'Не удалось прочитать изображение (повреждено или формат не поддерживается сервером).', 422);
    }
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180, 6 => -90, 8 => 90, default => 0,
        };
        if ($angle !== 0) {
            $rot = @imagerotate($src, $angle, 0);
            if ($rot !== false) {
                $src = $rot;
                $w = imagesx($src);
                $h = imagesy($src);
            }
        }
    }
    $scale = min(1.0, $maxSide / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // прозрачность PNG/WebP -> белый фон
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $dest = $dir . '/' . $baseName . '.jpg';
    $ok = imagejpeg($dst, $dest, $quality);
    imagedestroy($src);
    imagedestroy($dst);
    if (!$ok) {
        throw new ApiError('storage', 'Не удалось сохранить файл.', 500);
    }
    @chmod($dest, 0644);
    return ['file' => $rel . '/' . $baseName . '.jpg', 'w' => $nw, 'h' => $nh];
}

/** Понятный текст ошибки PHP-загрузки. */
function upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл больше лимита сервера (upload_max_filesize = ' . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_PARTIAL   => 'Файл загрузился не полностью. Повторите.',
        UPLOAD_ERR_NO_FILE   => 'Файл не выбран.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'На сервере нет места или временной папки для загрузки.',
        default              => 'Ошибка загрузки файла.',
    };
}

function category_exists(array $data, string $id): bool
{
    foreach ($data['categories'] as $c) {
        if (($c['id'] ?? '') === $id) {
            return true;
        }
    }
    return false;
}

/* ====================================================================
   4. РАЗМЕТКА ДЛЯ index.php И mobile.php
   ==================================================================== */

function kd_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Читает контент для страницы. При любой ошибке — пустой набор (страница всё равно откроется). */
function kd_public_content(): array
{
    try {
        $data = content_store()->read();
    } catch (Throwable $e) {
        log_line('warning', 'Контент главной страницы не прочитан: ' . $e->getMessage());
        return content_defaults();
    }
    // Файлы, которых уже нет на диске, не показываем: страница не должна выводить пустые рамки
    $root = dirname(__DIR__);
    $data['photos'] = array_values(array_filter($data['photos'], static fn($p) => is_file($root . '/' . (string) ($p['file'] ?? '-'))));
    $data['videos'] = array_values(array_filter($data['videos'], static fn($v) => is_file($root . '/' . (string) ($v['file'] ?? '-'))));
    return $data;
}

/**
 * Вкладки и панели галереи. $variant: 'desktop' (классы nav_button / gallery_images / gallery_img)
 * или 'mobile' (tab / gal_grid / gimg). Категории без фото не показываются.
 * @return array{tabs: string, panels: string}
 */
function kd_gallery_html(string $variant): array
{
    $data = kd_public_content();
    $mobile = $variant === 'mobile';
    $tabs = '';
    $panels = '';
    $first = true;
    foreach ($data['categories'] as $cat) {
        $cid = (string) $cat['id'];
        $photos = array_values(array_filter($data['photos'], static fn($p) => ($p['category'] ?? '') === $cid));
        if (!$photos) {
            continue;
        }
        $name = kd_h((string) $cat['name']);
        $idAttr = kd_h($cid);
        if ($mobile) {
            $tabs .= '        <button class="tab" type="button" role="tab" aria-selected="' . ($first ? 'true' : 'false') . '" data-gallery="' . $idAttr . '">' . $name . "</button>\n";
            $panels .= '      <div class="gal_grid" id="gallery_' . $idAttr . '"' . ($first ? '' : ' hidden') . ">\n";
        } else {
            $tabs .= '        <button class="nav_button' . ($first ? ' videleno' : '') . '" data-gallery="' . $idAttr . '">' . $name . "</button>\n";
            $panels .= '        <div class="gallery_images' . ($first ? '' : ' is-hidden') . '" id="gallery_' . $idAttr . '" data-gallery-panel="' . $idAttr . "\">\n";
        }
        $n = 0;
        foreach ($photos as $p) {
            $n++;
            $half = ($p['size'] ?? 'quarter') === 'half';
            $pos = in_array($p['pos'] ?? '', ['right', 'left'], true) ? ' pos_' . $p['pos'] : '';
            $alt = kd_h((string) (($p['alt'] ?? '') !== '' ? $p['alt'] : $cat['name'] . ' ' . $n));
            $src = kd_h((string) $p['file']);
            if ($mobile) {
                $panels .= '        <img src="' . $src . '" alt="' . $alt . '" class="gimg' . ($half ? ' large' : '') . $pos . '" data-group="' . $idAttr . '" loading="lazy" decoding="async">' . "\n";
            } else {
                $panels .= '          <img src="' . $src . '" alt="' . $alt . '" class="gallery_img ' . ($half ? 'large' : 'small') . $pos . '" loading="lazy" decoding="async">' . "\n";
            }
        }
        $panels .= $mobile ? "      </div>\n" : "        </div>\n";
        $first = false;
    }
    return ['tabs' => $tabs, 'panels' => $panels];
}

/** Видео главной страницы. Пустая строка, если видео нет (страница тогда скрывает блок). */
function kd_videos_html(string $variant): string
{
    $data = kd_public_content();
    $mobile = $variant === 'mobile';
    $out = '';
    $n = 0;
    foreach ($data['videos'] as $v) {
        $n++;
        $src = kd_h((string) $v['file']);
        $type = str_ends_with((string) $v['file'], '.webm') ? 'video/webm' : 'video/mp4';
        $poster = ($v['poster'] ?? '') !== '' ? ' poster="' . kd_h((string) $v['poster']) . '"' : '';
        if ($mobile) {
            $out .= '        <div class="vwrap" data-group="video" role="button" tabindex="0" aria-label="Смотреть видео ' . $n . "\">\n"
                . '          <video muted playsinline preload="metadata"' . $poster . ">\n"
                . '            <source src="' . $src . '#t=0.1" type="' . $type . "\">\n"
                . "          </video>\n"
                . "          <div class=\"play-icon\">▶</div>\n"
                . "        </div>\n";
        } else {
            $out .= "        <div class=\"video-wrapper\">\n"
                . '          <video class="gallery_img gallery_video" muted preload="metadata"' . $poster . ">\n"
                . '            <source src="' . $src . '" type="' . $type . "\">\n"
                . "          </video>\n"
                . "          <div class=\"play-icon\">▶</div>\n"
                . "        </div>\n";
        }
    }
    return $out;
}
