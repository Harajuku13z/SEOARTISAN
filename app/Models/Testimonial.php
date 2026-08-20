<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Testimonial extends Model
{
    protected static string $table = 'testimonials';

    protected static array $fillable = [
        'author_name', 'author_city', 'role_or_service', 'content', 'rating', 'reviewed_at',
        'source', 'google_review_id', 'avatar_media_id', 'is_visible', 'sort_order',
    ];

    protected static array $casts = ['is_visible' => 'bool', 'rating' => 'int'];

    /** @return array<int,self> */
    public static function visible(): array
    {
        return self::where(['is_visible' => 1], 'sort_order ASC');
    }

    /** @return array<int,self> */
    public static function visibleGoogle(): array
    {
        return self::where(['is_visible' => 1, 'source' => 'google'], 'sort_order ASC');
    }

    public static function findByGoogleReviewId(string $googleReviewId): ?self
    {
        return self::first(['google_review_id' => $googleReviewId]);
    }
}
