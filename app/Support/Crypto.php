<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * sodium_crypto_secretbox wrapper used to encrypt AI provider API keys at
 * rest. The key (APP_KEY) is a base64-encoded 32-byte secret generated
 * once by the installer and stored in .env - never in the database.
 */
final class Crypto
{
    public static function generateKey(): string
    {
        return base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    public static function encrypt(string $plaintext, string $base64Key): string
    {
        $key = self::decodeKey($base64Key);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);

        return base64_encode($nonce . $cipher);
    }

    public static function decrypt(string $encoded, string $base64Key): ?string
    {
        $key = self::decodeKey($base64Key);
        $raw = base64_decode($encoded, true);

        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plaintext = sodium_crypto_secretbox_open($cipher, $nonce, $key);

        return $plaintext === false ? null : $plaintext;
    }

    private static function decodeKey(string $base64Key): string
    {
        $key = base64_decode($base64Key, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_KEY is missing or invalid - cannot encrypt/decrypt.');
        }

        return $key;
    }
}
