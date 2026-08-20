<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal file-based cache. Good enough for a single shared-hosting
 * instance; no distributed invalidation is needed at this scale.
 */
final class Cache
{
    private static string $directory;

    public static function configure(string $directory): void
    {
        self::$directory = rtrim($directory, '/');
        if (!is_dir(self::$directory)) {
            mkdir(self::$directory, 0775, true);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return $default;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $payload = unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($payload) || !array_key_exists('expires_at', $payload) || !array_key_exists('value', $payload)) {
            return $default;
        }

        if ($payload['expires_at'] !== 0 && $payload['expires_at'] < time()) {
            @unlink($file);

            return $default;
        }

        return $payload['value'];
    }

    public static function put(string $key, mixed $value, int $ttlSeconds = 3600): void
    {
        $payload = [
            'expires_at' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
            'value' => $value,
        ];

        file_put_contents(self::file($key), serialize($payload), LOCK_EX);
    }

    public static function remember(string $key, int $ttlSeconds, callable $resolver): mixed
    {
        $cached = self::get($key, '__MISS__');
        if ($cached !== '__MISS__') {
            return $cached;
        }

        $value = $resolver();
        self::put($key, $value, $ttlSeconds);

        return $value;
    }

    public static function forget(string $key): void
    {
        @unlink(self::file($key));
    }

    public static function flush(): void
    {
        foreach (glob(self::$directory . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }

    private static function file(string $key): string
    {
        return self::$directory . '/' . hash('sha256', $key) . '.cache';
    }
}
