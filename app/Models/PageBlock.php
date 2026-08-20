<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PageBlock extends Model
{
    protected static string $table = 'page_blocks';

    protected static array $fillable = ['page_id', 'type', 'position', 'data', 'is_active'];

    protected static array $casts = ['data' => 'json', 'is_active' => 'bool'];

    /** @return array<int,self> */
    public static function forPage(int $pageId): array
    {
        return self::where(['page_id' => $pageId, 'is_active' => 1], 'position ASC');
    }
}
