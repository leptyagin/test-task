<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

abstract class Repository
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    protected function prepare(string $sql): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        if ($statement === false) {
            throw new RuntimeException('Couldnt prepare the request: ' . $sql);
        }

        return $statement;
    }

    protected function query(string $sql): PDOStatement
    {
        $statement = $this->pdo->query($sql);

        if ($statement === false) {
            throw new RuntimeException('Couldnt execute the query: ' . $sql);
        }

        return $statement;
    }

    protected function transactional(callable $callback): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $callback();
        }

        $this->pdo->beginTransaction();

        try {
            $result = $callback();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }
}
