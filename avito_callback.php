<?php
// Лежит в корне сайта: https://klevaydacha30.ru/avito_callback.php
require __DIR__ . '/avito/lib.php';
header('Content-Type: text/plain; charset=utf-8');

if (!isset($_GET['code'], $_GET['state']) || !hash_equals(AVITO_STATE, (string)$_GET['state'])) {
    http_response_code(400);
    exit('Неверный запрос');
}

try {
    exchange_code((string)$_GET['code']);
    echo 'Готово: доступ получен, токены сохранены. Эту страницу можно закрыть.';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Ошибка: ' . $e->getMessage();
}
