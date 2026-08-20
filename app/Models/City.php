<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class City extends Model
{
    protected static string $table = 'cities';

    protected static array $fillable = [
        'department_id', 'name', 'slug', 'postal_code', 'insee_code', 'latitude', 'longitude', 'population',
    ];

    protected static array $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'population' => 'int',
    ];
}
