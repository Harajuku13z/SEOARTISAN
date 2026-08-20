<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CompanySubcategory extends Model
{
    protected static string $table = 'company_subcategories';

    protected static array $fillable = ['company_id', 'business_subcategory_id'];
}
