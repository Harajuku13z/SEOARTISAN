<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CategoryService extends Model
{
    protected static string $table = 'category_services';

    protected static array $fillable = ['business_category_id', 'service_id', 'sort_order'];
}
