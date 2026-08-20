<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Project extends Model
{
    protected static string $table = 'projects';

    protected static array $fillable = [
        'title', 'description', 'category', 'city_id', 'company_service_id', 'project_date',
        'before_media_id', 'after_media_id', 'gallery', 'alt_text', 'is_visible', 'sort_order',
    ];

    protected static array $casts = ['gallery' => 'json', 'is_visible' => 'bool'];

    /** @return array<int,self> */
    public static function visible(): array
    {
        return self::where(['is_visible' => 1], 'sort_order ASC');
    }
}
