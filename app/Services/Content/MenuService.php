<?php
declare(strict_types=1);
namespace App\Services\Content;

use App\Models\Setting;
use App\Core\Cache;

final class MenuService
{
    private const KEY = 'navigation.menu';

    /** @return array<int,array<string,mixed>> */
    public static function items(): array
    {
        $raw = Setting::first(['key' => self::KEY])?->getAttribute('value');
        $items = json_decode((string) $raw, true);
        if (!is_array($items)) return [];
        usort($items, static fn ($a, $b) => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));
        return $items;
    }

    /** @param array<int,array<string,mixed>> $items */
    public static function save(array $items): void
    {
        $setting = Setting::first(['key' => self::KEY]) ?? new Setting();
        $setting->fill(['key' => self::KEY, 'value' => json_encode(array_values($items), JSON_UNESCAPED_UNICODE), 'autoload' => true])->save();
        Cache::flush();
    }

    /** @return array<int,array<string,mixed>> */
    public static function tree(): array
    {
        $items = array_values(array_filter(self::items(), static fn ($i) => !empty($i['active'])));
        $roots = [];
        foreach ($items as $item) {
            if (empty($item['parent_id'])) { $item['children'] = []; $roots[$item['id']] = $item; }
        }
        foreach ($items as $item) {
            $parent = (string)($item['parent_id'] ?? '');
            if ($parent !== '' && isset($roots[$parent])) $roots[$parent]['children'][] = $item;
        }
        return array_values($roots);
    }
}
