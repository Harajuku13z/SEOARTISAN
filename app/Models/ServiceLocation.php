<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ServiceLocation extends Model
{
    protected static string $table = 'service_locations';

    protected static array $fillable = ['company_service_id', 'city_id', 'is_active'];

    protected static array $casts = ['is_active' => 'bool'];
}
