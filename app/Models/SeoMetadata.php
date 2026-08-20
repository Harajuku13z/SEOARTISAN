<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class SeoMetadata extends Model
{
    protected static string $table = 'seo_metadata';

    protected static array $fillable = [
        'site_name', 'default_title_pattern', 'default_meta_description',
        'gsc_verification_code',
    ];

    public static function current(): ?self
    {
        $rows = self::all('id ASC');

        return $rows[0] ?? null;
    }
}
