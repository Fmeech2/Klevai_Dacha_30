<?php
// Необязательный JSON-вариант (пригодится, если захочешь грузить отзывы через JS).
require __DIR__ . '/lib.php';
header('Content-Type: application/json; charset=utf-8');

$data = get_reviews_data();
if ($data === null) {
    http_response_code(502);
    echo json_encode(['error' => 'avito_unavailable']);
    exit;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);