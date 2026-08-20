<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Minimal .env loader. No Composer dependency by design (see plan:
 * the app must boot on hosts without Composer/SSH access).
 */
final class Env
{
    /** @var array<string,string> */
    private static array $values = [];

    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = self::stripQuotesAndComment(trim($value));
            $value = self::interpolate($value);

            self::$values[$key] = $value;

            if (getenv($key) === false) {
                putenv("{$key}={$value}");
            }
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$values)) {
            return self::castScalar(self::$values[$key]);
        }

        $fromEnv = getenv($key);
        if ($fromEnv !== false) {
            return self::castScalar($fromEnv);
        }

        return $default;
    }

    private static function stripQuotesAndComment(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = $value[0];
        if (($first === '"' || $first === "'") && str_ends_with($value, $first) && strlen($value) >= 2) {
            return substr($value, 1, -1);
        }

        // Strip an unquoted trailing comment: KEY=value # comment
        $hashPos = strpos($value, ' #');
        if ($hashPos !== false) {
            $value = rtrim(substr($value, 0, $hashPos));
        }

        return $value;
    }

    private static function interpolate(string $value): string
    {
        return (string) preg_replace_callback('/\$\{([A-Z0-9_]+)\}/i', function (array $m): string {
            return self::$values[$m[1]] ?? (string) (getenv($m[1]) ?: '');
        }, $value);
    }

    private static function castScalar(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}
