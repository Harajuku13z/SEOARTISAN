<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Page extends Model
{
    protected static string $table = 'pages';

    protected static array $fillable = [
        'type', 'company_service_id', 'slug', 'title', 'h1', 'meta_title', 'meta_description',
        'canonical_url', 'og_media_id', 'schema_type', 'status', 'indexable', 'quality_score',
        'content_is_placeholder', 'author_id', 'published_at', 'last_generated_at',
    ];

    protected static array $casts = [
        'indexable' => 'bool',
        'content_is_placeholder' => 'bool',
        'quality_score' => 'int',
    ];

    public static function findBySlug(string $slug): ?self
    {
        return self::first(['slug' => $slug]);
    }

    public function isPublished(): bool
    {
        return $this->getAttribute('status') === 'published';
    }
}
