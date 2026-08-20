<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Runs numbered .sql files from database/migrations, tracked in a
 * `migrations` table. Used both by the web installer (shared hosts often
 * have no SSH/CLI access) and by scripts/migrate.php.
 */
final class Migrator
{
    public function __construct(
        private Database $db,
        private string $migrationsPath
    ) {
    }

    public function ensureMigrationsTable(): void
    {
        $table = $this->db->table('migrations');
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT UNSIGNED NOT NULL,
                run_at DATETIME NOT NULL,
                UNIQUE KEY uniq_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /** @return array<int,string> */
    public function allMigrationFiles(): array
    {
        $files = glob(rtrim($this->migrationsPath, '/') . '/*.sql') ?: [];
        sort($files);

        return array_map('basename', $files);
    }

    /** @return array<int,string> */
    public function appliedMigrations(): array
    {
        $this->ensureMigrationsTable();
        $table = $this->db->table('migrations');
        $rows = $this->db->select("SELECT migration FROM `{$table}` ORDER BY id ASC");

        return array_map(static fn (array $row) => (string) $row['migration'], $rows);
    }

    /** @return array<int,string> */
    public function pendingMigrations(): array
    {
        return array_values(array_diff($this->allMigrationFiles(), $this->appliedMigrations()));
    }

    /**
     * Runs every pending migration in order. Each file may contain several
     * statements separated by ";" on their own line.
     *
     * @return array<int,string> filenames that were applied
     */
    public function run(): array
    {
        $this->ensureMigrationsTable();
        $pending = $this->pendingMigrations();
        if ($pending === []) {
            return [];
        }

        $table = $this->db->table('migrations');
        $batch = (int) ($this->db->selectOne("SELECT COALESCE(MAX(batch), 0) AS max_batch FROM `{$table}`")['max_batch'] ?? 0) + 1;

        $applied = [];
        foreach ($pending as $file) {
            $sql = (string) file_get_contents(rtrim($this->migrationsPath, '/') . '/' . $file);
            $sql = $this->applyTablePrefix($sql);
            $this->executeSqlFile($sql);

            $this->db->insert(
                "INSERT INTO `{$table}` (migration, batch, run_at) VALUES (?, ?, NOW())",
                [$file, $batch]
            );

            $applied[] = $file;
        }

        return $applied;
    }

    /**
     * Drops only tables declared by this application's migration files.
     * Both prefixed and unprefixed variants are removed because older
     * installer versions could create unprefixed tables accidentally.
     */
    public function resetApplicationTables(): void
    {
        $tables = $this->applicationTableNames();
        $prefix = $this->db->prefix();
        $this->db->execute('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach (array_reverse($tables) as $table) {
                if ($prefix !== '') {
                    $this->db->execute('DROP TABLE IF EXISTS `' . $prefix . $table . '`');
                }
                $this->db->execute('DROP TABLE IF EXISTS `' . $table . '`');
            }
            $this->db->execute('DROP TABLE IF EXISTS `' . $prefix . 'migrations`');
        } finally {
            $this->db->execute('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    private function executeSqlFile(string $sql): void
    {
        $statements = array_filter(array_map('trim', explode(";\n", $this->stripComments($sql))));

        foreach ($statements as $statement) {
            $statement = trim($statement, " \t\n\r\0\x0B;");
            if ($statement === '') {
                continue;
            }
            $this->db->execute($statement);
        }
    }

    private function stripComments(string $sql): string
    {
        $lines = explode("\n", $sql);
        $lines = array_filter($lines, static fn (string $line) => !str_starts_with(trim($line), '--'));

        return implode("\n", $lines);
    }

    private function applyTablePrefix(string $sql): string
    {
        $prefix = $this->db->prefix();
        if ($prefix === '') {
            return $sql;
        }

        foreach ($this->applicationTableNames() as $table) {
            $sql = preg_replace(
                '/(?<![A-Za-z0-9_])`?' . preg_quote($table, '/') . '`?(?![A-Za-z0-9_])/',
                '`' . $prefix . $table . '`',
                $sql
            ) ?? $sql;
        }

        return $sql;
    }

    /** @return array<int,string> */
    private function applicationTableNames(): array
    {
        $tables = [];
        foreach ($this->allMigrationFiles() as $file) {
            $sql = (string) file_get_contents(rtrim($this->migrationsPath, '/') . '/' . $file);
            if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i', $sql, $matches)) {
                foreach ($matches[1] as $table) {
                    $tables[] = (string) $table;
                }
            }
        }

        return array_values(array_unique($tables));
    }
}
