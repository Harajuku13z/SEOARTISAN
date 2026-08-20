<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class BusinessSubcategory extends Model
{
    protected static string $table = 'business_subcategories';

    protected static array $fillable = [
        'business_category_id', 'slug', 'name', 'is_active', 'sort_order',
    ];

    protected static array $casts = ['is_active' => 'bool'];
}
