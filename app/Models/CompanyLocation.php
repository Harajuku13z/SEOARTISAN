<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CompanyLocation extends Model
{
    protected static string $table = 'company_locations';

    protected static array $fillable = [
        'company_id', 'city_id', 'is_primary', 'distance_km', 'is_active', 'seo_priority',
    ];

    protected static array $casts = [
        'is_primary' => 'bool',
        'is_active' => 'bool',
        'distance_km' => 'float',
    ];
}
