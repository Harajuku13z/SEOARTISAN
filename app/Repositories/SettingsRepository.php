<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Setting;

/**
 * Key/value store on top of the `settings` table. Every value is
 * JSON-encoded on write and decoded on read - unconditionally, not
 * heuristically - so a stored string like "1" or "null" round-trips
 * exactly instead of being misread as an int or null.
 */
final class SettingsRepository
{
    public function __construct(private Database $db)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::first(['key' => $key]);
        if ($setting === null) {
            return $default;
        }

        $raw = $setting->getAttribute('value');
        if ($raw === null) {
            return null;
        }

        return json_decode((string) $raw, true);
    }

    public function set(string $key, mixed $value): void
    {
        $stored = json_encode($value, JSON_UNESCAPED_UNICODE);

        $setting = Setting::first(['key' => $key]);
        if ($setting === null) {
            Setting::create(['key' => $key, 'value' => $stored, 'autoload' => 1]);

            return;
        }

        $setting->setAttribute('value', $stored);
        $setting->save();
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        $result = [];
        foreach (Setting::all() as $setting) {
            $raw = $setting->getAttribute('value');
            $result[$setting->getAttribute('key')] = $raw === null ? null : json_decode((string) $raw, true);
        }

        return $result;
    }
}
