<?php

declare(strict_types=1);

// Standalone regression checks: php tests/smtp-settings.php (PDO SQLite required).
// All records are kept in memory. No SMTP connection or real e-mail is sent.
require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\Admin\CompanyController;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\CsrfMiddleware;
use App\Models\Setting;
use App\Services\Auth\AuthService;
use App\Services\Mail\SmtpSettings;
use App\Services\Media\MediaUploadService;
use App\Support\Crypto;

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db = Database::configure([]);
(new ReflectionProperty(Database::class, 'pdo'))->setValue($db, $pdo);
$pdo->exec('CREATE TABLE settings (id INTEGER PRIMARY KEY AUTOINCREMENT, `key` TEXT UNIQUE, value TEXT, autoload INTEGER)');
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, role TEXT)');
$pdo->exec('CREATE TABLE activity_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, subject_type TEXT, subject_id INTEGER, description TEXT, ip_address TEXT)');
$pdo->exec("INSERT INTO users VALUES (1, 'super_admin'), (2, 'editor')");
Config::set('app.key', Crypto::generateKey());
$environmentPassword = 'fixture-password-$' . '{literal}-"quoted"';
Config::set('mail', ['host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'tls', 'username' => 'contact@example.com', 'password' => $environmentPassword, 'from' => ['address' => 'contact@example.com', 'name' => 'Fixture'], 'reply_to' => '']);

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$service = new SmtpSettings();
$check($service->current()['password'] === $environmentPassword, 'Initial settings must retain the environment password.');
$form = $service->forForm();
$check(!array_key_exists('password', $form) && $form['password_configured'], 'The form must not contain the password.');
$input = [];
foreach ($form as $key => $value) {
    if ($key !== 'password_configured') {
        $input['smtp_' . $key] = (string) $value;
    }
}
$input['smtp_password'] = '';
$input['smtp_from_name'] = 'Nouvel expéditeur';
$service->save($input);
$stored = (string) Setting::first(['key' => 'mail.smtp'])->getAttribute('value');
$check(!str_contains($stored, $environmentPassword), 'The database must not contain the plaintext password.');
$check($service->current()['password'] === $environmentPassword && $service->current()['from_name'] === 'Nouvel expéditeur', 'Blank password must preserve the existing credential while saving other settings.');
$check(Setting::first(['key' => 'mail.smtp'])->getAttribute('autoload') === false, 'SMTP secrets must not be autoloaded.');

foreach ([['smtp_port' => '0'], ['smtp_port' => '65536'], ['smtp_port' => '587abc'], ['smtp_host' => 'https://smtp.example.com'], ['smtp_host' => ''], ['smtp_encryption' => 'none'], ['smtp_from_address' => 'invalid'], ['smtp_reply_to' => "contact@example.com\r\nBcc:attacker@example.com"], ['smtp_username' => ['bad']], ['smtp_password' => "invalid\npassword"]] as $invalid) {
    try {
        $service->save(array_replace($input, $invalid));
        throw new RuntimeException('Invalid SMTP input was accepted.');
    } catch (InvalidArgumentException) {
        $check(Setting::first(['key' => 'mail.smtp'])->getAttribute('value') === $stored, 'Invalid input must not modify the saved configuration.');
    }
}

$input['smtp_password'] = 'replacement-password';
$input['smtp_encryption'] = 'ssl';
$input['smtp_port'] = '465';
$service->save($input);
$check($service->current()['password'] === 'replacement-password' && $service->current()['encryption'] === 'ssl' && $service->current()['port'] === 465, 'New credentials and implicit TLS must persist.');
Config::set('app.key', Crypto::generateKey());
$check(!$service->forForm()['password_configured'] && isset($service->forForm()['configuration_error']), 'An unreadable password must leave the settings screen usable.');
try {
    $service->current();
    throw new RuntimeException('Unreadable credentials must not silently fall back.');
} catch (RuntimeException $e) {
    $check($e->getMessage() === 'Impossible de lire le mot de passe SMTP enregistré.', 'Mail sending must fail clearly for an unreadable secret.');
}
$service->save($input);
$check($service->current()['password'] === 'replacement-password', 'Entering a new password must recover the configuration.');

$_SESSION = [];
$_GET = $_FILES = [];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/admin/company/smtp';
$_POST = $input;
$controller = new CompanyController(new AuthService([]), new MediaUploadService([]));
Session::put('auth_user_id', 2);
$before = Setting::first(['key' => 'mail.smtp'])->getAttribute('value');
$check($controller->updateSmtp(new Request())->status() === 403, 'Editors must not update SMTP settings.');
$check(Setting::first(['key' => 'mail.smtp'])->getAttribute('value') === $before, 'Forbidden updates must leave settings unchanged.');
Session::put('auth_user_id', 1);
$middleware = new CsrfMiddleware();
$check($middleware->handle(new Request(), static fn () => Response::text('unexpected'))->status() === 419, 'SMTP writes require a CSRF token.');
$_POST['_csrf_token'] = Csrf::token();
$response = $middleware->handle(new Request(), fn (Request $request) => $controller->updateSmtp($request));
$check($response->status() === 302, 'Authorized saves must redirect.');
$check($pdo->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn() === 1, 'Successful saves must create an audit entry.');
$_POST['smtp_port'] = 'invalid';
$controller->updateSmtp(new Request());
$check(!isset($_SESSION['_flash_next']['_smtp_input']['password']) && !str_contains(json_encode($_SESSION), 'replacement-password'), 'Validation errors must never flash the submitted password.');

echo "SMTP settings: {$checks} checks passed.\n";
