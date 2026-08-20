<?php

declare(strict_types=1);

/**
 * Application bootstrap: autoloader, environment, configuration and core
 * services wiring. Required by both the HTTP front controller
 * (public/index.php) and CLI scripts (scripts/migrate.php).
 *
 * Deliberately does NOT depend on vendor/autoload.php - the app must boot
 * on hosts with no Composer/SSH access. Composer is only used here for
 * PHPUnit (dev dependency) and IDE autocompletion.
 */

define('BASE_PATH', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\' => '/app/',
        'Database\\Seeders\\' => '/database/seeders/',
        'Tests\\' => '/tests/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $path = BASE_PATH . $directory . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require $path;
        }

        return;
    }
});

require BASE_PATH . '/app/Support/helpers.php';

use App\Core\Cache;
use App\Core\Config;
use App\Core\Container;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\View;
use App\Support\Env;

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config');

date_default_timezone_set((string) Config::get('app.timezone', 'Europe/Paris'));

Logger::configure(Config::get('app.paths.storage') . '/logs', (string) Env::get('LOG_LEVEL', 'info'));
ErrorHandler::register((bool) Config::get('app.debug', false));
Cache::configure(Config::get('app.paths.storage') . '/cache');
View::configure(Config::get('app.paths.resources') . '/views');

// The database may not be configured yet (installer runs before it is).
// Database::configure() only stores the config; the actual PDO connection
// is opened lazily on first use, so this is always safe to call.
$dbConfig = Config::get('database');
if (!empty($dbConfig['database'])) {
    Database::configure($dbConfig);
}

return Container::getInstance();
