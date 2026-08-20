<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Rewrites KEY=VALUE lines in a .env file, preserving comments and
 * ordering. Used by the installer's database step (writes DB_* + APP_KEY)
 * - one of the only places this app writes to its own configuration file.
 */
final class EnvWriter
{
    /** @param array<string,string> $values */
    public static function update(string $path, array $values): void
    {
        $lines = is_file($path) ? (file($path, FILE_IGNORE_NEW_LINES) ?: []) : [];
        $remaining = $values;

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key] = explode('=', $trimmed, 2);
            $key = trim($key);

            if (array_key_exists($key, $remaining)) {
                $lines[$index] = $key . '=' . self::formatValue($remaining[$key]);
                unset($remaining[$key]);
            }
        }

        foreach ($remaining as $key => $value) {
            $lines[] = $key . '=' . self::formatValue($value);
        }

        file_put_contents($path, implode("\n", $lines) . "\n", LOCK_EX);
    }

    private static function formatValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|"/', $value)) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }

        return $value;
    }
}
