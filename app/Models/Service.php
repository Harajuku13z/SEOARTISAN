<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Service extends Model
{
    protected static string $table = 'services';

    protected static array $fillable = ['slug', 'name', 'default_description', 'icon', 'is_active'];

    protected static array $casts = ['is_active' => 'bool'];
}
