<?php

declare(strict_types=1);

namespace App\Services\Mail;

use RuntimeException;

final class SmtpMailer
{
    public function sendHtml(string $to, string $subject, string $html, ?string $replyTo = null): void
    {
        $settings = (new SmtpSettings())->current();
        $host = (string) $settings['host'];
        $port = (int) $settings['port'];
        $encryption = (string) $settings['encryption'];
        $username = (string) $settings['username'];
        $password = (string) $settings['password'];
        $from = (string) $settings['from_address'];
        $fromName = (string) $settings['from_name'];
        $replyTo = $replyTo ?: (string) $settings['reply_to'];

        if ((!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
                && !filter_var($host, FILTER_VALIDATE_IP))
            || $port < 1 || $port > 65535 || !in_array($encryption, ['tls', 'ssl'], true)
            || $username === '' || $password === '') {
            throw new RuntimeException('Configuration SMTP incomplète ou invalide.');
        }

        $message = $this->buildMessage($to, $subject, $html, $from, $fromName, $replyTo);
        $hello = parse_url((string) config('app.url', ''), PHP_URL_HOST) ?: 'localhost';
        if (!filter_var($hello, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $hello = 'localhost';
        }

        $context = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ]]);
        $transport = $encryption === 'ssl' ? 'tls' : 'tcp';
        $address = str_contains($host, ':') ? '[' . $host . ']' : $host;
        $socket = @stream_socket_client(
            $transport . '://' . $address . ':' . $port,
            $number,
            $error,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!is_resource($socket)) {
            throw new RuntimeException('Connexion SMTP impossible (' . $number . '). Vérifiez le serveur, le port et le chiffrement.');
        }
        stream_set_timeout($socket, 15);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO ' . $hello, [250]);
            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Activation TLS SMTP impossible.');
                }
                $this->command($socket, 'EHLO ' . $hello, [250]);
            }
            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($username), [334]);
            $this->command($socket, base64_encode($password), [235]);
            $this->command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);
            $this->write($socket, $message . "\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function buildMessage(string $to, string $subject, string $html, string $from, string $fromName, string $replyTo): string
    {
        $addresses = [$to, $from];
        if ($replyTo !== '') {
            $addresses[] = $replyTo;
        }
        foreach ($addresses as $address) {
            if (preg_match('/[\r\n\x00]/', $address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Adresse e-mail invalide.');
            }
        }
        foreach ([$subject, $fromName] as $header) {
            if (preg_match('/[\r\n\x00]/', $header) || !mb_check_encoding($header, 'UTF-8')) {
                throw new RuntimeException('En-tête e-mail invalide.');
            }
        }

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->encode($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $this->encode($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
        ];
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }
        $html = str_replace(["\r\n", "\r"], "\n", $html);
        $body = quoted_printable_encode(str_replace("\n", "\r\n", $html));
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;

        return (string) preg_replace('/^\./m', '..', $message);
    }

    private function command($socket, string $command, array $codes): string
    {
        if (preg_match('/[\r\n\x00]/', $command)) {
            throw new RuntimeException('Commande SMTP invalide.');
        }
        $this->write($socket, $command . "\r\n");
        return $this->expect($socket, $codes);
    }

    private function write($socket, string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Écriture SMTP impossible.');
            }
            $offset += $written;
        }
    }

    private function expect($socket, array $codes): string
    {
        $response = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false || !preg_match('/^\d{3}[ -]/', $line)) {
                throw new RuntimeException('Le serveur SMTP ne répond pas correctement.');
            }
            $response .= $line;
            if (strlen($response) > 65536) {
                throw new RuntimeException('Réponse SMTP trop longue.');
            }
        } while ($line[3] === '-');
        $code = (int) substr($line, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('Erreur SMTP ' . $code . '. Vérifiez la configuration et les adresses.');
        }
        return $response;
    }

    private function encode(string $value): string
    {
        $parts = [];
        while ($value !== '') {
            $part = mb_strcut($value, 0, 42, 'UTF-8');
            $parts[] = '=?UTF-8?B?' . base64_encode($part) . '?=';
            $value = substr($value, strlen($part));
        }
        return implode("\r\n ", $parts);
    }
}
