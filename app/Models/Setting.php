<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Setting extends Model
{
    protected static string $table = 'settings';

    protected static array $fillable = ['key', 'value', 'autoload'];

    protected static array $casts = ['autoload' => 'bool'];
}
