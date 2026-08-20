<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Loads every file under /config into a nested array keyed by filename,
 * exposed via dot notation (e.g. config('database.connections.mysql.host')).
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];

    private static bool $loaded = false;

    public static function load(string $configDir): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        foreach (glob(rtrim($configDir, '/') . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &self::$items;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $target[$segment] = $value;
                break;
            }
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }
    }

    /** @return array<string,mixed> */
    public static function all(): array
    {
        return self::$items;
    }
}
