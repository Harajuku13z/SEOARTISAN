<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CompanyService extends Model
{
    protected static string $table = 'company_services';

    protected static array $fillable = [
        'company_id', 'service_id', 'public_name', 'slug', 'description', 'content_mode',
        'manual_content', 'is_custom',
        'show_in_menu', 'is_featured', 'is_emergency', 'show_starting_price', 'starting_price',
        'image_media_id', 'sort_order', 'meta_title', 'meta_description', 'is_active',
    ];

    protected static array $casts = [
        'is_custom' => 'bool',
        'show_in_menu' => 'bool',
        'is_featured' => 'bool',
        'is_emergency' => 'bool',
        'show_starting_price' => 'bool',
        'is_active' => 'bool',
        'starting_price' => 'float',
    ];
}
