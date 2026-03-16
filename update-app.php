<?php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'app_update.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Use POST to run the updater.',
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $config = veloura_load_config();
    $manifest = veloura_load_manifest($config);
    $payload = veloura_update_payload();

    if (empty($payload['update_available'])) {
        echo json_encode([
            'success' => true,
            'message' => 'The application is already up to date.',
        ] + $payload, JSON_PRETTY_PRINT);
        exit;
    }

    $result = veloura_apply_update($config, $manifest);
    $freshPayload = veloura_update_payload(true);

    echo json_encode([
        'success' => true,
        'message' => 'Application updated successfully.',
        'backup_file' => $result['backup_file'],
        'migrations' => $result['migrations'],
    ] + $freshPayload, JSON_PRETTY_PRINT);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT);
}
