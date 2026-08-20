<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'session' => [
        'name' => Env::get('SESSION_NAME', 'artisan_ia_pro_session'),
        'lifetime_minutes' => (int) Env::get('SESSION_LIFETIME', 120),
        'secure_cookie' => filter_var(Env::get('SESSION_SECURE_COOKIE', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'csrf' => [
        'token_name' => '_csrf_token',
    ],

    // password_hash() algorithm: ARGON2ID when the running PHP build
    // supports it, bcrypt otherwise (some shared-hosting builds lack
    // Argon2 support even on PHP 8.2+).
    'password_algo' => in_array(PASSWORD_ARGON2ID, password_algos(), true)
        ? PASSWORD_ARGON2ID
        : PASSWORD_BCRYPT,

    'login_rate_limit' => [
        'max_attempts' => 5,
        'decay_minutes' => 15,
    ],

    'uploads' => [
        'max_size_bytes' => 20 * 1024 * 1024, // 20 MB
        'video_max_size_bytes' => 200 * 1024 * 1024, // 200 MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'],
        'allowed_video_extensions' => ['mp4', 'webm', 'mov'],
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ],
        'allowed_video_mime_types' => [
            'video/mp4',
            'video/webm',
            'video/quicktime',
        ],
    ],
];
