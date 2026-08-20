<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Company extends Model
{
    protected static string $table = 'companies';

    protected static array $fillable = [
        'business_category_id',
        'trade_name', 'legal_name', 'slogan', 'short_description', 'long_description', 'founded_year',
        'phone', 'whatsapp', 'public_email', 'leads_email',
        'address', 'postal_code', 'city', 'department', 'region',
        'siret', 'certifications', 'opening_hours', 'social_links', 'gbp_url', 'google_maps_data_id', 'legal_info',
        'service_radius_km', 'offers_emergency', 'offers_free_quote',
        'primary_color', 'secondary_color', 'accent_color', 'button_style', 'font_primary', 'font_secondary', 'theme_style',
        'logo_main_media_id', 'logo_light_media_id', 'logo_dark_media_id', 'favicon_media_id', 'hero_media_id', 'hero_mobile_media_id', 'og_media_id',
        'editorial_presentation', 'editorial_history', 'editorial_experience', 'editorial_values',
        'editorial_work_method', 'editorial_advantages', 'editorial_guarantees', 'editorial_client_types',
        'editorial_achievements', 'editorial_brands_used', 'editorial_typical_delays', 'editorial_commitments',
        'editorial_differentiators', 'editorial_priority_areas',
    ];

    protected static array $casts = [
        'certifications' => 'json',
        'opening_hours' => 'json',
        'social_links' => 'json',
        'offers_emergency' => 'bool',
        'offers_free_quote' => 'bool',
    ];

    /**
     * The app supports a single company per install (MVP). Returns the
     * first (and only) row, or null before the installer has run.
     */
    public static function current(): ?self
    {
        $rows = self::all('id ASC');

        return $rows[0] ?? null;
    }
}
