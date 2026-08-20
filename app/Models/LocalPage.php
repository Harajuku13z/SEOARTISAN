<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class LocalPage extends Model
{
    protected static string $table = 'local_pages';

    protected static array $fillable = [
        'company_service_id', 'city_id', 'page_id', 'is_active', 'content_mode',
        'content_payload', 'status', 'generation_status', 'error_message',
        'uniqueness_score', 'quality_score', 'published_at', 'last_generated_at',
    ];

    protected static array $casts = [
        'is_active' => 'bool', 'content_payload' => 'json',
        'uniqueness_score' => 'int', 'quality_score' => 'int',
    ];
}
