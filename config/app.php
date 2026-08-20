<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'name' => Env::get('APP_NAME', 'Artisan IA Pro'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => (bool) Env::get('APP_DEBUG', false),
    'url' => rtrim((string) Env::get('APP_URL', 'http://localhost'), '/'),
    'key' => Env::get('APP_KEY', ''),
    'timezone' => 'Europe/Paris',
    'locale' => 'fr',

    'paths' => [
        'base' => BASE_PATH,
        'app' => BASE_PATH . '/app',
        'config' => BASE_PATH . '/config',
        'database' => BASE_PATH . '/database',
        'public' => BASE_PATH . '/public',
        'resources' => BASE_PATH . '/resources',
        'storage' => BASE_PATH . '/storage',
        'routes' => BASE_PATH . '/routes',
    ],
];
