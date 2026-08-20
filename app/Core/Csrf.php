<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_tokens';

    public static function token(): string
    {
        $tokens = Session::get(self::SESSION_KEY, []);
        if (empty($tokens['current'])) {
            $tokens['current'] = bin2hex(random_bytes(32));
            Session::put(self::SESSION_KEY, $tokens);
        }

        return $tokens['current'];
    }

    public static function field(): string
    {
        $name = Config::get('security.csrf.token_name', '_csrf_token');

        return '<input type="hidden" name="' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') . '" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $tokens = Session::get(self::SESSION_KEY, []);
        $current = $tokens['current'] ?? null;

        return is_string($current) && hash_equals($current, $token);
    }
}
