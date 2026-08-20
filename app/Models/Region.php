<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Region extends Model
{
    protected static string $table = 'regions';

    protected static array $fillable = ['name', 'code'];
}
