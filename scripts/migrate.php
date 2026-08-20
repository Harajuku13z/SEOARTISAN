<?php

declare(strict_types=1);

/**
 * CLI migration runner - same Migrator the web installer uses (Step 2).
 * Useful for local development or on hosts that do offer SSH.
 *
 * Usage: php scripts/migrate.php [--status]
 */

use App\Core\Database;
use App\Core\Migrator;

require_once dirname(__DIR__) . '/bootstrap.php';

$dbConfig = config('database');
if (empty($dbConfig['database'])) {
    fwrite(STDERR, "Database is not configured. Copy .env.example to .env and set DB_* values first.\n");
    exit(1);
}

Database::configure($dbConfig);
$migrator = new Migrator(Database::instance(), database_path('migrations'));

if (in_array('--status', $argv, true)) {
    $applied = $migrator->appliedMigrations();
    $pending = $migrator->pendingMigrations();

    echo "Applied (" . count($applied) . "):\n";
    foreach ($applied as $file) {
        echo "  [x] {$file}\n";
    }

    echo "Pending (" . count($pending) . "):\n";
    foreach ($pending as $file) {
        echo "  [ ] {$file}\n";
    }

    exit(0);
}

$applied = $migrator->run();

if ($applied === []) {
    echo "Nothing to migrate - database is up to date.\n";
    exit(0);
}

echo "Applied " . count($applied) . " migration(s):\n";
foreach ($applied as $file) {
    echo "  - {$file}\n";
}
