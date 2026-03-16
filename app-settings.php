<?php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'app_update.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $config = veloura_load_config();
        echo json_encode([
            'success' => true,
            'manifest_url' => (string) ($config['updates']['manifest_url'] ?? ''),
        ], JSON_PRETTY_PRINT);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Use GET to read or POST to save application settings.',
        ], JSON_PRETTY_PRINT);
        exit;
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode((string) $raw, true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $manifestUrl = trim((string) ($payload['manifest_url'] ?? ''));
    if ($manifestUrl !== '' && filter_var($manifestUrl, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('Please enter a valid manifest URL.');
    }

    $config = veloura_load_config();
    $config['updates']['manifest_url'] = $manifestUrl;
    veloura_save_config($config);

    echo json_encode([
        'success' => true,
        'manifest_url' => $manifestUrl,
        'message' => 'Application settings saved successfully.',
    ], JSON_PRETTY_PRINT);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT);
}
