<?php
return [
    'installed' => false,
    'app_url' => 'https://your-domain.com',
    'version' => '1.0.0',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'cpanel_database_name',
        'user' => 'cpanel_database_user',
        'pass' => 'database_password',
        'charset' => 'utf8mb4',
    ],
    'updates' => [
        'manifest_url' => '',
        'manifest_path' => 'updates/latest-version.json',
        'current_version' => '1.0.0',
        'last_checked_at' => null,
        'last_updated_at' => null,
    ],
];
