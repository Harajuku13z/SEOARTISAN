<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Models\Setting;
use App\Support\Crypto;
use InvalidArgumentException;
use RuntimeException;

final class SmtpSettings
{
    private const KEY = 'mail.smtp';

    /** Existing environment configuration remains active until the first save. */
    public function current(): array
    {
        return $this->load();
    }

    private function load(bool $forForm = false): array
    {
        $settings = [
            'host' => (string) config('mail.host', ''),
            'port' => (int) config('mail.port', 587),
            'encryption' => (string) config('mail.encryption', 'tls'),
            'username' => (string) config('mail.username', ''),
            'password' => (string) config('mail.password', ''),
            'from_address' => (string) config('mail.from.address', ''),
            'from_name' => (string) config('mail.from.name', ''),
            'reply_to' => (string) config('mail.reply_to', ''),
        ];
        $row = Setting::first(['key' => self::KEY]);
        if ($row === null) {
            return $settings;
        }

        $stored = json_decode((string) $row->getAttribute('value'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($stored) || !isset($stored['password_encrypted'])) {
            throw new RuntimeException('Configuration SMTP enregistrée invalide.');
        }
        foreach (array_keys($settings) as $key) {
            if ($key !== 'password' && array_key_exists($key, $stored)) {
                $settings[$key] = $stored[$key];
            }
        }
        try {
            $password = Crypto::decrypt((string) $stored['password_encrypted'], (string) config('app.key'));
        } catch (RuntimeException $e) {
            if (!$forForm) {
                throw $e;
            }
            $password = null;
        }
        if ($password === null) {
            if (!$forForm) {
                throw new RuntimeException('Impossible de lire le mot de passe SMTP enregistré.');
            }
            $settings['configuration_error'] = 'Le mot de passe enregistré ne peut plus être lu. Saisissez à nouveau votre mot de passe SMTP et enregistrez.';
        }
        $settings['password'] = $password ?? '';

        return $settings;
    }

    /** The secret is never passed to the HTML view or flashed into the session. */
    public function forForm(): array
    {
        $settings = $this->load(true);
        $settings['password_configured'] = $settings['password'] !== '';
        unset($settings['password']);

        return $settings;
    }

    /** @param array<string,mixed> $input */
    public function save(array $input): void
    {
        $settings = $this->validate($input);
        $settings['password_encrypted'] = Crypto::encrypt($settings['password'], (string) config('app.key'));
        unset($settings['password']);

        $row = Setting::first(['key' => self::KEY]) ?? new Setting();
        $row->fill([
            'key' => self::KEY,
            'value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'autoload' => false,
        ])->save();
    }

    /** Validate everything before changing the saved configuration. */
    private function validate(array $input): array
    {
        $settings = [];
        foreach (['host', 'port', 'encryption', 'username', 'password', 'from_address', 'from_name', 'reply_to'] as $key) {
            $value = $input['smtp_' . $key] ?? '';
            if (!is_string($value) || preg_match('/[\x00-\x1F\x7F]/', $value)) {
                throw new InvalidArgumentException('Les paramètres SMTP doivent être du texte sans retour à la ligne.');
            }
            $settings[$key] = $key === 'password' ? $value : trim($value);
        }

        if (strlen($settings['host']) > 253 || (!filter_var($settings['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            && !filter_var($settings['host'], FILTER_VALIDATE_IP))) {
            throw new InvalidArgumentException('Indiquez un serveur SMTP valide, sans https:// ni numéro de port.');
        }
        if (!ctype_digit($settings['port']) || (int) $settings['port'] < 1 || (int) $settings['port'] > 65535) {
            throw new InvalidArgumentException('Le port SMTP doit être compris entre 1 et 65535.');
        }
        $settings['port'] = (int) $settings['port'];
        if (!in_array($settings['encryption'], ['tls', 'ssl'], true)) {
            throw new InvalidArgumentException('Choisissez STARTTLS ou SSL/TLS pour la connexion SMTP.');
        }
        if ($settings['username'] === '' || strlen($settings['username']) > 255) {
            throw new InvalidArgumentException('Renseignez l’identifiant SMTP (255 caractères maximum).');
        }
        if (!filter_var($settings['from_address'], FILTER_VALIDATE_EMAIL)
            || ($settings['reply_to'] !== '' && !filter_var($settings['reply_to'], FILTER_VALIDATE_EMAIL))) {
            throw new InvalidArgumentException('Vérifiez les adresses e-mail d’expédition et de réponse.');
        }
        if (strlen($settings['from_name']) > 150) {
            throw new InvalidArgumentException('Le nom de l’expéditeur ne doit pas dépasser 150 caractères.');
        }
        if ($settings['password'] === '') {
            $settings['password'] = $this->current()['password'];
        }
        if ($settings['password'] === '' || strlen($settings['password']) > 4096) {
            throw new InvalidArgumentException('Renseignez le mot de passe SMTP avant d’enregistrer.');
        }

        return $settings;
    }
}
