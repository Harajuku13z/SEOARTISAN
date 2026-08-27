<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Visit extends Model
{
    protected static string $table = 'visits';

    protected static array $fillable = [
        'path', 'is_bot', 'bot_category', 'bot_name', 'user_agent',
        'ip_hash', 'referrer', 'status_code',
    ];

    protected static array $casts = ['is_bot' => 'bool'];
}
