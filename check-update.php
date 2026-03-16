<?php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'app_update.php';

try {
    echo json_encode(veloura_update_payload(true), JSON_PRETTY_PRINT);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT);
}
