<?php
/**
 * КЛЁВАЯ ДАЧА — кнопка «Позвонить» из уведомления в Telegram.
 *
 * Telegram не разрешает ссылки tel: в кнопках, поэтому кнопка ведёт на эту страницу,
 * а страница открывает набор номера клиента. Ссылка подписана (HMAC): без подписи номер заявки
 * подобрать нельзя. В самой ссылке нет ни имени, ни телефона, только номер заявки и подпись.
 */

declare(strict_types=1);

define('KD_BOOKING', true);
require __DIR__ . '/lib.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');

function call_page_fail(int $status, string $message): never
{
    http_response_code($status);
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Ссылка недействительна</title><p style="font:18px system-ui,sans-serif;padding:24px">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

try {
    kd_bootstrap();
    $id = (string) ($_GET['r'] ?? '');
    $sig = (string) ($_GET['s'] ?? '');
    if (!preg_match('/^req_[0-9a-f]{16}$/', $id) || !hash_equals(call_link_signature($id), $sig)) {
        usleep(300000);
        call_page_fail(404, 'Ссылка недействительна.');
    }
    $found = null;
    foreach ((new Store())->read()['requests'] as $r) {
        if (($r['id'] ?? '') === $id) {
            $found = $r;
            break;
        }
    }
} catch (Throwable $e) {
    log_line('error', 'call.php: ' . $e->getMessage());
    call_page_fail(500, 'Сервис временно недоступен. Позвоните клиенту по номеру из сообщения в Telegram.');
}

$phone = $found !== null ? (string) ($found['phone'] ?? '') : '';
if ($found === null || !preg_match('/^\+\d{8,15}$/', $phone)) {
    call_page_fail(404, 'Заявка не найдена.');
}

$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'none'; style-src 'nonce-$nonce'; script-src 'nonce-$nonce'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
$name = trim((string) $found['firstName'] . ' ' . (string) ($found['lastName'] ?? ''));
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Позвонить клиенту</title>
  <style nonce="<?= $nonce ?>">
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #F3F6FB; font: 17px/1.45 system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1F1F1F; }
    main { width: min(420px, 100% - 32px); padding: 28px 22px; background: #fff; border-radius: 22px; text-align: center; box-shadow: 0 4px 24px rgba(31, 41, 55, .08); }
    .muted { margin: 0 0 4px; color: #8b93a1; font-size: 14px; }
    h1 { margin: 0 0 18px; font-size: 24px; }
    a.btn { display: block; padding: 16px 20px; border-radius: 999px; background: #56CCF2; color: #fff; font-weight: 700; font-size: 19px; text-decoration: none; }
    p.hint { margin: 16px 0 0; color: #8b93a1; font-size: 14px; }
  </style>
</head>
<body>
  <main>
    <p class="muted">Заявка № <?= (int) $found['number'] ?></p>
    <h1><?= $h($name !== '' ? $name : 'Клиент') ?></h1>
    <a class="btn" id="call" href="tel:<?= $h($phone) ?>">Позвонить <?= $h(format_phone($phone)) ?></a>
    <p class="hint">Если набор номера не открылся сам, нажмите кнопку.</p>
  </main>
  <script nonce="<?= $nonce ?>">
    setTimeout(function () { location.href = document.getElementById('call').href; }, 300);
  </script>
</body>
</html>
