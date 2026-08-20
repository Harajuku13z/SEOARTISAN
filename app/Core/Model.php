<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin ActiveRecord-lite base. Deliberately minimal - no relationships,
 * no query builder beyond simple equality WHERE. Anything join-heavy or
 * list-heavy belongs in a Repository (app/Repositories), not here.
 *
 * Only attributes declared in $fillable are ever written back to the
 * database - this is the app's mass-assignment guard.
 */
abstract class Model
{
    protected static string $table;

    protected static string $primaryKey = 'id';

    /** @var array<int,string> */
    protected static array $fillable = [];

    /** @var array<string,string> column => 'json'|'bool'|'int'|'float' */
    protected static array $casts = [];

    /** @var array<string,mixed> */
    protected array $attributes = [];

    protected bool $exists = false;

    /** @param array<string,mixed> $attributes */
    final public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /** @param array<string,mixed> $attributes */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        if (!array_key_exists($key, $this->attributes)) {
            return null;
        }

        return $this->castFromStorage($key, $this->attributes[$key]);
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function id(): int|string|null
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->attributes as $key => $value) {
            $result[$key] = $this->castFromStorage($key, $value);
        }

        return $result;
    }

    protected static function db(): Database
    {
        return Database::instance();
    }

    protected static function tableName(): string
    {
        return static::db()->table(static::$table);
    }

    public static function find(int|string $id): ?static
    {
        $row = static::db()->selectOne(
            'SELECT * FROM ' . static::tableName() . ' WHERE ' . static::$primaryKey . ' = ? LIMIT 1',
            [$id]
        );

        return $row === null ? null : static::newFromRow($row);
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            throw new \RuntimeException(static::class . " #{$id} not found.");
        }

        return $model;
    }

    /** @return array<int,static> */
    public static function all(?string $orderBy = null): array
    {
        $sql = 'SELECT * FROM ' . static::tableName();
        if ($orderBy !== null) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        return array_map(static fn (array $row) => static::newFromRow($row), static::db()->select($sql));
    }

    /**
     * Simple equality AND conditions only. Anything more complex belongs
     * in a Repository, written as raw SQL against Database directly.
     *
     * @param array<string,mixed> $conditions
     * @return array<int,static>
     */
    public static function where(array $conditions, ?string $orderBy = null, ?int $limit = null): array
    {
        [$sql, $params] = static::buildWhere($conditions);
        $sql = 'SELECT * FROM ' . static::tableName() . $sql;

        if ($orderBy !== null) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        return array_map(static fn (array $row) => static::newFromRow($row), static::db()->select($sql, $params));
    }

    /** @param array<string,mixed> $conditions */
    public static function first(array $conditions): ?static
    {
        $rows = static::where($conditions, null, 1);

        return $rows[0] ?? null;
    }

    /** @param array<string,mixed> $conditions */
    public static function count(array $conditions = []): int
    {
        [$sql, $params] = static::buildWhere($conditions);
        $row = static::db()->selectOne('SELECT COUNT(*) AS c FROM ' . static::tableName() . $sql, $params);

        return (int) ($row['c'] ?? 0);
    }

    /** @param array<string,mixed> $attributes */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    public function save(): bool
    {
        return $this->exists ? $this->performUpdate() : $this->performInsert();
    }

    public function delete(): bool
    {
        if (!$this->exists || $this->id() === null) {
            return false;
        }

        static::db()->execute(
            'DELETE FROM ' . static::tableName() . ' WHERE ' . static::$primaryKey . ' = ?',
            [$this->id()]
        );

        $this->exists = false;

        return true;
    }

    private function performInsert(): bool
    {
        // created_at/updated_at are never set here: every migration defines
        // them with DEFAULT CURRENT_TIMESTAMP / ON UPDATE CURRENT_TIMESTAMP,
        // so MySQL manages them without app-layer intervention.
        $data = $this->fillableAttributesForStorage();
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::tableName(),
            implode(', ', array_map(static fn (string $c) => "`{$c}`", $columns)),
            implode(', ', $placeholders)
        );

        $id = static::db()->insert($sql, array_values($data));

        if (!isset($this->attributes[static::$primaryKey])) {
            $this->attributes[static::$primaryKey] = $id;
        }

        $this->exists = true;

        return true;
    }

    private function performUpdate(): bool
    {
        $data = $this->fillableAttributesForStorage();
        unset($data[static::$primaryKey]);

        if ($data === []) {
            return true;
        }

        $assignments = implode(', ', array_map(static fn (string $c) => "`{$c}` = ?", array_keys($data)));
        $sql = 'UPDATE ' . static::tableName() . " SET {$assignments} WHERE " . static::$primaryKey . ' = ?';

        static::db()->execute($sql, [...array_values($data), $this->id()]);

        return true;
    }

    /** @return array<string,mixed> */
    private function fillableAttributesForStorage(): array
    {
        $data = [];
        foreach (static::$fillable as $key) {
            if (array_key_exists($key, $this->attributes)) {
                $data[$key] = $this->castForStorage($key, $this->attributes[$key]);
            }
        }
        // Allow the primary key to be explicitly set (e.g. seeders using fixed ids).
        if (isset($this->attributes[static::$primaryKey])) {
            $data[static::$primaryKey] = $this->attributes[static::$primaryKey];
        }

        return $data;
    }

    protected function castFromStorage(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match (static::$casts[$key] ?? null) {
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'bool' => (bool) $value,
            'int' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }

    protected function castForStorage(string $key, mixed $value): mixed
    {
        return match (static::$casts[$key] ?? null) {
            'json' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value,
            'bool' => $value === null ? null : (int) (bool) $value,
            default => $value,
        };
    }

    /**
     * @param array<string,mixed> $conditions
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function buildWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['', []];
        }

        $clauses = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $clauses[] = "`{$column}` IS NULL";
                continue;
            }
            $clauses[] = "`{$column}` = ?";
            $params[] = $value;
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    /** @param array<string,mixed> $row */
    protected static function newFromRow(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->exists = true;

        return $model;
    }
}
