<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'driver' => Env::get('MAIL_DRIVER', 'mail'),
    'host' => Env::get('MAIL_HOST', 'smtp.mail.ovh.net'),
    'port' => (int) Env::get('MAIL_PORT', 587),
    'username' => Env::get('MAIL_USERNAME', ''),
    'password' => Env::get('MAIL_PASSWORD', ''),
    'reply_to' => Env::get('MAIL_REPLY_TO', ''),
    'from' => [
        'address' => Env::get('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => Env::get('MAIL_FROM_NAME', Env::get('APP_NAME', 'Artisan IA Pro')),
    ],
];
