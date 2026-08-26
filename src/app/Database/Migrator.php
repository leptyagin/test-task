<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $path,
    ) {
    }

    /**
     * @return list<string>
     */
    public function migrate(): array
    {
        $this->ensureLedger();

        $applied = $this->applied();
        $batch = $this->currentBatch() + 1;

        $insert = $this->pdo->prepare(
            'INSERT INTO migrations (migration, batch, applied_at) VALUES (?, ?, ?)',
        );

        if ($insert === false) {
            throw new RuntimeException('Migration log request could not be prepared');
        }

        $ran = [];

        foreach ($this->files() as $name => $file) {
            if (\in_array($name, $applied, true)) {
                continue;
            }

            $this->load($file)->up($this->pdo);
            $insert->execute([$name, $batch, date('Y-m-d H:i:s')]);
            $ran[] = $name;
        }

        return $ran;
    }

    /**
     * @return list<string>
     */
    public function rollback(): array
    {
        $this->ensureLedger();

        $batch = $this->currentBatch();

        if ($batch === 0) {
            return [];
        }

        $statement = $this->pdo->prepare(
            'SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC',
        );

        if ($statement === false) {
            throw new RuntimeException('Migration log request could not be prepared');
        }

        $statement->execute([$batch]);

        $files = $this->files();
        $delete = $this->pdo->prepare('DELETE FROM migrations WHERE migration = ?');

        if ($delete === false) {
            throw new RuntimeException('Migration log request could not be prepared');
        }

        $rolled = [];

        foreach ($statement->fetchAll() as $row) {
            /** @var array<string, mixed> $row */
            $name = (string) $row['migration'];

            if (!isset($files[$name])) {
                throw new RuntimeException("Migration file not found: {$name}");
            }

            $this->load($files[$name])->down($this->pdo);
            $delete->execute([$name]);
            $rolled[] = $name;
        }

        return $rolled;
    }

    /**
     * @return array<string, bool>
     */
    public function status(): array
    {
        $this->ensureLedger();

        $applied = $this->applied();
        $status = [];

        foreach (array_keys($this->files()) as $name) {
            $status[$name] = \in_array($name, $applied, true);
        }

        return $status;
    }

    private function ensureLedger(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations ('
            . ' id INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . ' migration VARCHAR(255) NOT NULL,'
            . ' batch INT UNSIGNED NOT NULL,'
            . ' applied_at DATETIME NOT NULL,'
            . ' PRIMARY KEY (id),'
            . ' UNIQUE KEY uq_migrations_migration (migration)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        );
    }

    /**
     * @return list<string>
     */
    private function applied(): array
    {
        $statement = $this->pdo->query('SELECT migration FROM migrations ORDER BY id');

        if ($statement === false) {
            return [];
        }

        $names = [];

        foreach ($statement->fetchAll() as $row) {
            /** @var array<string, mixed> $row */
            $names[] = (string) $row['migration'];
        }

        return $names;
    }

    private function currentBatch(): int
    {
        $statement = $this->pdo->query('SELECT MAX(batch) AS batch FROM migrations');

        if ($statement === false) {
            return 0;
        }

        $row = $statement->fetch();

        if (!\is_array($row) || !isset($row['batch'])) {
            return 0;
        }

        return (int) $row['batch'];
    }

    /**
     * @return array<string, string>
     */
    private function files(): array
    {
        $files = glob(rtrim($this->path, '/') . '/*.php');

        if ($files === false) {
            return [];
        }

        sort($files);

        $map = [];

        foreach ($files as $file) {
            $map[basename($file, '.php')] = $file;
        }

        return $map;
    }

    private function load(string $file): Migration
    {
        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new RuntimeException("Migration file must return an instance of App\\Database\\Migration: {$file}");
        }

        return $migration;
    }
}
