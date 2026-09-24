<?php

declare(strict_types=1);

// Standalone transport regression checks. All socket operations are intercepted;
// this script never connects to a server or sends an e-mail.
namespace App\Services\Mail {
    final class SmtpSettings
    {
        public function current(): array
        {
            return FakeSmtp::$settings;
        }
    }

    final class FakeSmtp
    {
        public static array $settings;
        public static array $responses;
        public static array $context = [];
        public static string $address = '';
        public static string $sent = '';
        public static int $connections = 0;
        public static int $cryptoCalls = 0;
        public static bool $cryptoSuccess = true;
        public static mixed $socket;

        public static function reset(string $encryption = 'tls'): void
        {
            self::$settings = [
                'host' => 'smtp.example.com', 'port' => $encryption === 'tls' ? 587 : 465,
                'encryption' => $encryption, 'username' => 'sender@example.com',
                'password' => 'local-test-only', 'from_address' => 'sender@example.com',
                'from_name' => 'Entreprise française', 'reply_to' => 'reply@example.com',
            ];
            self::$responses = ["220 ready\r\n", "250-example.com\r\n", "250 AUTH LOGIN\r\n"];
            if ($encryption === 'tls') {
                self::$responses = array_merge(self::$responses, ["220 TLS ready\r\n", "250 hello again\r\n"]);
            }
            self::$responses = array_merge(self::$responses, [
                "334 username\r\n", "334 password\r\n", "235 authenticated\r\n",
                "250 sender\r\n", "250 recipient\r\n", "354 data\r\n",
                "250 accepted\r\n", "221 bye\r\n",
            ]);
            self::$sent = '';
            self::$connections = 0;
            self::$cryptoCalls = 0;
            self::$cryptoSuccess = true;
        }
    }

    function stream_socket_client(string $address, &$number, &$error, $timeout, $flags, $context)
    {
        FakeSmtp::$connections++;
        FakeSmtp::$address = $address;
        FakeSmtp::$context = stream_context_get_options($context);
        return FakeSmtp::$socket = tmpfile();
    }

    function stream_set_timeout($socket, int $timeout): bool
    {
        return true;
    }

    function stream_socket_enable_crypto($socket, bool $enable, int $method): bool
    {
        FakeSmtp::$cryptoCalls++;
        return FakeSmtp::$cryptoSuccess;
    }

    function fwrite($socket, string $data): int
    {
        // Force short writes to check that the complete payload is transmitted.
        $part = substr($data, 0, 17);
        FakeSmtp::$sent .= $part;
        return strlen($part);
    }

    function fgets($socket, int $length): string|false
    {
        return array_shift(FakeSmtp::$responses) ?? false;
    }
}

namespace {
    use App\Services\Mail\FakeSmtp;
    use App\Services\Mail\SmtpMailer;

    function config(string $key, mixed $fallback = null): mixed
    {
        return $key === 'app.url' ? 'https://www.example.com' : $fallback;
    }

    require dirname(__DIR__) . '/app/Services/Mail/SmtpMailer.php';

    $checks = 0;
    function check(bool $condition, string $description): void
    {
        global $checks;
        $checks++;
        if (!$condition) {
            throw new RuntimeException('Failed: ' . $description);
        }
    }

    function expectFailure(callable $action, string $description): void
    {
        try {
            $action();
        } catch (RuntimeException $error) {
            check(true, $description);
            return;
        }
        check(false, $description);
    }

    $mailer = new SmtpMailer();
    $html = ".first\n..second\r\n.\r<p>Été = soleil</p>\n" . str_repeat('long ', 300);
    $subject = str_repeat('Été 🏡 ', 18);
    foreach (['tls', 'ssl'] as $encryption) {
        FakeSmtp::reset($encryption);
        $mailer->sendHtml('recipient@example.com', $subject, $html);
        $wire = FakeSmtp::$sent;
        check(FakeSmtp::$address === ($encryption === 'tls' ? 'tcp://smtp.example.com:587' : 'tls://smtp.example.com:465'), 'transport');
        check(FakeSmtp::$cryptoCalls === ($encryption === 'tls' ? 1 : 0), 'STARTTLS only for explicit TLS');
        check(substr_count($wire, "EHLO www.example.com\r\n") === ($encryption === 'tls' ? 2 : 1), 'EHLO negotiation');
        check(str_contains($wire, "STARTTLS\r\n") === ($encryption === 'tls'), 'STARTTLS command');
        check(FakeSmtp::$context['ssl']['verify_peer'] && FakeSmtp::$context['ssl']['verify_peer_name'], 'certificate verification');
        check(!FakeSmtp::$context['ssl']['allow_self_signed'], 'self-signed certificates rejected');
        check(FakeSmtp::$context['ssl']['peer_name'] === 'smtp.example.com', 'certificate hostname');
        check(!str_contains($wire, "\r\r\n"), 'no double CR');
        check(!preg_match('/(?<!\r)\n|\r(?!\n)/', $wire), 'all line endings are CRLF');
        check(str_contains($wire, "\r\n\r\n..first\r\n...second\r\n..\r\n"), 'dot-stuffing including first body line');
        check(str_contains($wire, "Reply-To: <reply@example.com>\r\n"), 'configured reply-to');
        check(str_ends_with($wire, "\r\n.\r\nQUIT\r\n"), 'data terminator and QUIT');
        check(FakeSmtp::$responses === [], 'all responses consumed');
        check(!is_resource(FakeSmtp::$socket), 'socket closed');
        $data = explode("DATA\r\n", $wire, 2)[1];
        $data = substr($data, 0, -strlen("\r\n.\r\nQUIT\r\n"));
        [$headers, $body] = explode("\r\n\r\n", $data, 2);
        $body = preg_replace('/^\.\./m', '.', $body);
        check(quoted_printable_decode($body) === str_replace("\n", "\r\n", str_replace(["\r\n", "\r"], "\n", $html)), 'HTML body round-trip');
        check(max(array_map('strlen', explode("\r\n", $data))) <= 998, 'SMTP line limits');
        preg_match_all('/=\?UTF-8\?B\?([^?]+)\?=/', $headers, $encoded);
        foreach ($encoded[1] as $word) {
            check(mb_check_encoding(base64_decode($word), 'UTF-8'), 'encoded words preserve UTF-8 characters');
        }
    }

    foreach ([
        ['from_address', "sender@example.com\r\nRCPT TO:<intruder@example.com>"],
        ['from_address', ''], ['reply_to', 'invalid'],
        ['from_name', "Company\nBcc: intruder@example.com"],
        ['from_name', "Invalid \xC3"],
        ['host', "smtp.example.com\r\n"], ['host', 'smtp://example.com/path'],
        ['port', 0], ['port', 65536], ['encryption', 'none'], ['username', ''], ['password', ''],
    ] as [$field, $value]) {
        FakeSmtp::reset();
        FakeSmtp::$settings[$field] = $value;
        expectFailure(fn () => $mailer->sendHtml('recipient@example.com', 'Subject', '<p>Hello</p>'), 'invalid ' . $field);
        check(FakeSmtp::$connections === 0, 'invalid settings rejected before connecting');
    }
    foreach ([['invalid', 'Subject', null], ['recipient@example.com', "Subject\r\nBcc: x@example.com", null], ['recipient@example.com', 'Subject', "a@example.com\r\n"]] as [$to, $title, $reply]) {
        FakeSmtp::reset();
        expectFailure(fn () => $mailer->sendHtml($to, $title, '', $reply), 'header injection or invalid address');
        check(FakeSmtp::$connections === 0, 'headers rejected before connecting');
    }

    FakeSmtp::reset();
    FakeSmtp::$cryptoSuccess = false;
    expectFailure(fn () => $mailer->sendHtml('recipient@example.com', 'Subject', ''), 'TLS failure');
    check(!str_contains(FakeSmtp::$sent, 'AUTH LOGIN'), 'credentials never sent after TLS failure');
    check(!is_resource(FakeSmtp::$socket), 'socket closed after TLS failure');

    FakeSmtp::reset('ssl');
    FakeSmtp::$responses[5] = "535 Authentication failed\r\n";
    expectFailure(fn () => $mailer->sendHtml('recipient@example.com', 'Subject', ''), 'authentication failure');
    check(!str_contains(FakeSmtp::$sent, 'MAIL FROM:'), 'no envelope after authentication failure');
    check(!is_resource(FakeSmtp::$socket), 'socket closed after authentication failure');

    echo 'SMTP transport: ' . $checks . " checks passed; no network connection or e-mail sent.\n";
}
