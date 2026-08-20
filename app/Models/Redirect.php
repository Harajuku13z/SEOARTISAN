<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Redirect extends Model
{
    protected static string $table = 'redirects';

    protected static array $fillable = ['from_path', 'to_path', 'status_code', 'is_active', 'hit_count'];

    protected static array $casts = ['is_active' => 'bool', 'status_code' => 'int', 'hit_count' => 'int'];

    public static function findActiveFor(string $path): ?self
    {
        return self::first(['from_path' => $path, 'is_active' => 1]);
    }
}
