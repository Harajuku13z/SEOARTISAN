<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $started = false;

    /** @param array<string,mixed> $config */
    public static function start(array $config): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;

            return;
        }

        session_name((string) ($config['name'] ?? 'app_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (bool) ($config['secure_cookie'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.gc_maxlifetime', (string) (((int) ($config['lifetime_minutes'] ?? 120)) * 60));

        session_start();
        self::$started = true;

        // Rotate the id periodically to limit fixation/hijack windows.
        $now = time();
        if (empty($_SESSION['_last_regen']) || $now - (int) $_SESSION['_last_regen'] > 900) {
            session_regenerate_id(true);
            $_SESSION['_last_regen'] = $now;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_next'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_current'][$key] ?? $default;
    }

    /**
     * Call once per request, after routing/controllers ran, right before
     * the response is sent - promotes next-request flash data and expires
     * the previous batch. In practice the front controller calls this at
     * the very start of the NEXT request (see bootstrap.php).
     */
    public static function ageFlashData(): void
    {
        $_SESSION['_flash_current'] = $_SESSION['_flash_next'] ?? [];
        $_SESSION['_flash_next'] = [];
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$started = false;
    }

    public static function id(): string
    {
        return session_id() ?: '';
    }
}
