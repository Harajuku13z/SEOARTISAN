<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Department extends Model
{
    protected static string $table = 'departments';

    protected static array $fillable = ['region_id', 'name', 'code'];
}
