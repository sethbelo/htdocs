<?php

return [
    'runtime' => env('APP_RUNTIME_MODE', 'cloud'),
    'enabled' => env('OFFLINE_SYNC_ENABLED', false),
    'transport' => env('OFFLINE_SYNC_TRANSPORT', 'http'),
    'remote_url' => rtrim((string) env('OFFLINE_SYNC_URL', ''), '/'),
    'token' => env('OFFLINE_SYNC_TOKEN'),
    'connection' => env('OFFLINE_SYNC_DB_CONNECTION', 'central'),
    'timeout' => (int) env('OFFLINE_SYNC_TIMEOUT', 12),
    'batch_size' => (int) env('OFFLINE_SYNC_BATCH_SIZE', 50),
    'interval_seconds' => (int) env('OFFLINE_SYNC_INTERVAL', 15),
    'max_attempts' => (int) env('OFFLINE_SYNC_MAX_ATTEMPTS', 100),
    'backoff_seconds' => [15, 30, 60, 120, 300, 600, 1800],
];
