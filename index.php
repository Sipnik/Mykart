<?php
declare(strict_types=1);

$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$isInstalled = false;

if (file_exists($configFile)) {
    $config = include $configFile;
    $isInstalled = is_array($config) && !empty($config['installed']);
}

$target = $isInstalled ? 'index.html' : 'install.php';

header('Location: ' . $target, true, 302);
exit;
