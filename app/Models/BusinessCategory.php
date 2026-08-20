<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class BusinessCategory extends Model
{
    protected static string $table = 'business_categories';

    protected static array $fillable = [
        'slug', 'name', 'icon', 'schema_org_type', 'description', 'is_active', 'sort_order',
    ];

    protected static array $casts = ['is_active' => 'bool'];
}
