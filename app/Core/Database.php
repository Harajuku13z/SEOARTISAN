<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Thin PDO wrapper. Prepared statements only - no raw string interpolation
 * of user input is ever allowed through this class.
 */
final class Database
{
    private static ?Database $instance = null;

    private ?PDO $pdo = null;

    private string $prefix;

    /** @param array<string,mixed> $config */
    public function __construct(private array $config)
    {
        $this->prefix = (string) ($config['prefix'] ?? '');
    }

    public static function configure(array $config): self
    {
        self::$instance = new self($config);

        return self::$instance;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Database::configure() must be called before Database::instance().');
        }

        return self::$instance;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function table(string $name): string
    {
        return $this->prefix . $name;
    }

    /**
     * @throws PDOException
     */
    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->connect($this->config);
        }

        return $this->pdo;
    }

    /**
     * Attempts a connection without keeping it, used by the installer's
     * "test connection" action. Returns [true, null] or [false, error].
     *
     * @param array<string,mixed> $config
     * @return array{0: bool, 1: ?string}
     */
    public static function test(array $config): array
    {
        try {
            $pdo = (new self($config))->connect($config);
            $pdo = null;

            return [true, null];
        } catch (PDOException $e) {
            return [false, $e->getMessage()];
        }
    }

    /** @param array<string,mixed> $config */
    private function connect(array $config): PDO
    {
        $charset = $config['charset'] ?? 'utf8mb4';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'] ?? '127.0.0.1',
            (int) ($config['port'] ?? 3306),
            $config['database'] ?? '',
            $charset
        );

        return new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
        ]);
    }

    /** @param array<int|string,mixed> $params */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * @param array<int|string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * @param array<int|string,mixed> $params
     * @return array<string,mixed>|null
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<int|string,mixed> $params */
    public function insert(string $sql, array $params = []): int
    {
        $this->run($sql, $params);

        return (int) $this->pdo()->lastInsertId();
    }

    /** @param array<int|string,mixed> $params */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($this);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
