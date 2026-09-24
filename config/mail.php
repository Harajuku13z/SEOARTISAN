<?php

declare(strict_types=1);

use App\Support\Env;

$encryption = strtolower(trim((string) Env::get('MAIL_ENCRYPTION', 'tls')));

return [
    'driver' => Env::get('MAIL_DRIVER', 'mail'),
    'host' => Env::get('MAIL_HOST', 'smtp.mail.ovh.net'),
    'port' => (int) Env::get('MAIL_PORT', 587),
    'encryption' => match ($encryption) {
        'starttls' => 'tls',
        'smtps' => 'ssl',
        default => $encryption,
    },
    'username' => Env::get('MAIL_USERNAME', ''),
    'password' => Env::get('MAIL_PASSWORD', ''),
    'reply_to' => Env::get('MAIL_REPLY_TO', ''),
    'from' => [
        'address' => Env::get('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => Env::get('MAIL_FROM_NAME', Env::get('APP_NAME', 'Artisan IA Pro')),
    ],
];
