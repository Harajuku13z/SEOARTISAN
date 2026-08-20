<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AiProvider extends Model
{
    protected static string $table = 'ai_providers';

    protected static array $fillable = [
        'provider', 'api_key_encrypted', 'model', 'base_url', 'temperature', 'max_tokens',
        'language', 'tone', 'is_active',
    ];

    protected static array $casts = [
        'is_active' => 'bool',
        'temperature' => 'float',
        'max_tokens' => 'int',
    ];

    public static function active(): ?self
    {
        return self::first(['is_active' => 1]);
    }
}
