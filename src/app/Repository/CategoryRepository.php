<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;

final class CategoryRepository extends Repository
{
    private const COLUMNS = 'id, name, description, created_at, updated_at';

    /**
     * @return list<Category>
     */
    public function all(): array
    {
        $rows = $this->query('SELECT ' . self::COLUMNS . ' FROM categories ORDER BY name')->fetchAll();

        $categories = [];

        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $categories[] = $this->hydrate($row);
        }

        return $categories;
    }

    public function find(int $id): ?Category
    {
        $statement = $this->prepare('SELECT ' . self::COLUMNS . ' FROM categories WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if (!\is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->hydrate($row);
    }

    /**
     * @param list<int> $ids
     *
     * @return list<Category>
     */
    public function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, \count($ids), '?'));
        $statement = $this->prepare(
            'SELECT ' . self::COLUMNS . " FROM categories WHERE id IN ({$placeholders}) ORDER BY name",
        );
        $statement->execute(array_values($ids));

        $categories = [];

        foreach ($statement->fetchAll() as $row) {
            /** @var array<string, mixed> $row */
            $categories[] = $this->hydrate($row);
        }

        return $categories;
    }

    public function save(Category $category): Category
    {
        if ($category->id === null) {
            $statement = $this->prepare(
                'INSERT INTO categories (name, description) VALUES (:name, :description)',
            );
            $statement->execute([
                'name' => $category->name,
                'description' => $category->description,
            ]);
            $category->id = (int) $this->pdo->lastInsertId();

            return $category;
        }

        $statement = $this->prepare(
            'UPDATE categories SET name = :name, description = :description WHERE id = :id',
        );
        $statement->execute([
            'name' => $category->name,
            'description' => $category->description,
            'id' => $category->id,
        ]);

        return $category;
    }

    public function delete(int $id): void
    {
        $this->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Превращает строку из БД в сущность.
     *
     * @param array<string, mixed> $row
     */
    public function hydrate(array $row): Category
    {
        $category = new Category();
        $category->id = isset($row['id']) ? (int) $row['id'] : null;
        $category->name = (string) ($row['name'] ?? '');
        $category->description = isset($row['description']) ? (string) $row['description'] : null;
        $category->createdAt = isset($row['created_at']) ? (string) $row['created_at'] : null;
        $category->updatedAt = isset($row['updated_at']) ? (string) $row['updated_at'] : null;

        return $category;
    }
}
