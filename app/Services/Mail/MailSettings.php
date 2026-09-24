<?php
declare(strict_types=1);
namespace App\Services\Mail;

use App\Models\Setting;
use App\Support\Crypto;
use Throwable;

/**
 * SMTP configuration editable from /admin/company (tab "E-mails").
 * Values saved in the `mail.smtp` setting override the MAIL_* entries of .env;
 * the password is encrypted with APP_KEY when libsodium is available.
 */
final class MailSettings
{
    public const KEY = 'mail.smtp';

    /** @return array{host:string,port:int,encryption:string,username:string,password:string,from_address:string,from_name:string,reply_to:string} */
    public static function resolved(): array
    {
        $saved = self::saved();
        $password = '';
        if (($saved['password'] ?? '') !== '') {
            $password = self::decode((string) $saved['password']);
        }

        return [
            'host' => (string) (($saved['host'] ?? '') ?: config('mail.host', '')),
            'port' => (int) (($saved['port'] ?? 0) ?: config('mail.port', 587)),
            'encryption' => (string) (($saved['encryption'] ?? '') ?: (string) \App\Support\Env::get('MAIL_ENCRYPTION', 'tls')),
            'username' => (string) (($saved['username'] ?? '') ?: config('mail.username', '')),
            'password' => $password !== '' ? $password : (string) config('mail.password', ''),
            'from_address' => (string) (($saved['from_address'] ?? '') ?: config('mail.from.address', '')),
            'from_name' => (string) (($saved['from_name'] ?? '') ?: config('mail.from.name', config('app.name', 'Site Artisan'))),
            'reply_to' => (string) (($saved['reply_to'] ?? '') ?: config('mail.reply_to', '')),
        ];
    }

    /** @return array<string,mixed> Saved values, password excluded (for the admin form). */
    public static function forForm(): array
    {
        $saved = self::saved();
        $hasPassword = ($saved['password'] ?? '') !== '' || (string) config('mail.password', '') !== '';
        unset($saved['password']);
        $resolved = self::resolved();
        unset($resolved['password']);

        return array_merge($resolved, ['has_password' => $hasPassword]);
    }

    /** @param array<string,mixed> $input */
    public static function save(array $input): void
    {
        $current = self::saved();
        $data = [
            'host' => trim((string) ($input['host'] ?? '')),
            'port' => (int) ($input['port'] ?? 0) ?: 587,
            'encryption' => in_array($input['encryption'] ?? 'tls', ['tls', 'ssl'], true) ? (string) $input['encryption'] : 'tls',
            'username' => trim((string) ($input['username'] ?? '')),
            'from_address' => trim((string) ($input['from_address'] ?? '')),
            'from_name' => trim((string) ($input['from_name'] ?? '')),
            'reply_to' => trim((string) ($input['reply_to'] ?? '')),
            'password' => (string) ($current['password'] ?? ''),
        ];
        $newPassword = (string) ($input['password'] ?? '');
        if ($newPassword !== '') {
            $data['password'] = self::encode($newPassword);
        }
        $setting = Setting::first(['key' => self::KEY]) ?? new Setting();
        $setting->fill(['key' => self::KEY, 'value' => json_encode($data, JSON_UNESCAPED_UNICODE), 'autoload' => 0])->save();
    }

    /** @return array<string,mixed> */
    private static function saved(): array
    {
        try {
            $value = Setting::first(['key' => self::KEY])?->getAttribute('value');
        } catch (Throwable) {
            return [];
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function encode(string $password): string
    {
        $key = (string) config('app.key', '');
        if (function_exists('sodium_crypto_secretbox') && $key !== '') {
            try {
                return 'enc:' . Crypto::encrypt($password, $key);
            } catch (Throwable) {
            }
        }

        return 'b64:' . base64_encode($password);
    }

    private static function decode(string $stored): string
    {
        if (str_starts_with($stored, 'enc:')) {
            try {
                return (string) Crypto::decrypt(substr($stored, 4), (string) config('app.key', ''));
            } catch (Throwable) {
                return '';
            }
        }
        if (str_starts_with($stored, 'b64:')) {
            return (string) base64_decode(substr($stored, 4), true);
        }

        return $stored;
    }
}
