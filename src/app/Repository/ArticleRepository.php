<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use PDO;

final class ArticleRepository extends Repository
{
    private const COLUMNS = 'id, image, title, description, text, views, created_at, updated_at';

    private CategoryRepository $categories;

    public function __construct(?PDO $pdo = null, ?CategoryRepository $categories = null)
    {
        parent::__construct($pdo);
        $this->categories = $categories ?? new CategoryRepository($this->pdo);
    }

    /**
     * @return list<Article>
     */
    public function all(): array
    {
        $rows = $this->query(
            'SELECT ' . self::COLUMNS . ' FROM articles ORDER BY created_at DESC, id DESC',
        )->fetchAll();

        $articles = [];

        foreach ($rows as $row) {
            /** @var array<string, mixed> $row */
            $articles[] = $this->hydrate($row);
        }

        $this->attachCategories($articles);

        return $articles;
    }

    public function find(int $id): ?Article
    {
        $statement = $this->prepare('SELECT ' . self::COLUMNS . ' FROM articles WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if (!\is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        $article = $this->hydrate($row);
        $this->attachCategories([$article]);

        return $article;
    }

    public function save(Article $article): Article
    {
        return $this->transactional(function () use ($article): Article {
            if ($article->id === null) {
                $statement = $this->prepare(
                    'INSERT INTO articles (image, title, description, text, views)
                     VALUES (:image, :title, :description, :text, :views)',
                );
                $statement->execute($this->params($article));
                $article->id = (int) $this->pdo->lastInsertId();
            } else {
                $statement = $this->prepare(
                    'UPDATE articles
                     SET image = :image, title = :title, description = :description,
                         text = :text, views = :views
                     WHERE id = :id',
                );
                $statement->execute($this->params($article) + ['id' => $article->id]);
            }

            $this->syncCategories($article);

            return $article;
        });
    }

    public function incrementViews(int $id): void
    {
        $this->prepare('UPDATE articles SET views = views + 1 WHERE id = :id')->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->prepare('DELETE FROM articles WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Догружает категории для набора статей одним запросом.
     *
     * @param list<Article> $articles
     */
    private function attachCategories(array $articles): void
    {
        $ids = [];

        foreach ($articles as $article) {
            if ($article->id !== null) {
                $ids[$article->id] = $article->id;
            }
        }

        foreach ($articles as $article) {
            $article->categories = [];
        }

        if ($ids === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, \count($ids), '?'));
        $statement = $this->prepare(
            'SELECT ac.article_id, c.id, c.name, c.description, c.created_at, c.updated_at
             FROM article_category ac
             JOIN categories c ON c.id = ac.category_id
             WHERE ac.article_id IN (' . $placeholders . ')
             ORDER BY c.name',
        );
        $statement->execute(array_values($ids));

        /** @var array<int, list<\App\Entity\Category>> $byArticle */
        $byArticle = [];

        foreach ($statement->fetchAll() as $row) {
            /** @var array<string, mixed> $row */
            $articleId = (int) $row['article_id'];
            $byArticle[$articleId][] = $this->categories->hydrate($row);
        }

        foreach ($articles as $article) {
            if ($article->id !== null && isset($byArticle[$article->id])) {
                $article->categories = $byArticle[$article->id];
            }
        }
    }

    private function syncCategories(Article $article): void
    {
        if ($article->id === null) {
            return;
        }

        $this->prepare('DELETE FROM article_category WHERE article_id = :id')
            ->execute(['id' => $article->id]);

        if ($article->categories === []) {
            return;
        }

        $insert = $this->prepare(
            'INSERT INTO article_category (article_id, category_id) VALUES (:article_id, :category_id)',
        );

        foreach ($article->categoryIds() as $categoryId) {
            $insert->execute(['article_id' => $article->id, 'category_id' => $categoryId]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function params(Article $article): array
    {
        return [
            'image' => $article->image,
            'title' => $article->title,
            'description' => $article->description,
            'text' => $article->text,
            'views' => $article->views,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    public function hydrate(array $row): Article
    {
        $article = new Article();
        $article->id = isset($row['id']) ? (int) $row['id'] : null;
        $article->image = isset($row['image']) ? (string) $row['image'] : null;
        $article->title = (string) ($row['title'] ?? '');
        $article->description = isset($row['description']) ? (string) $row['description'] : null;
        $article->text = (string) ($row['text'] ?? '');
        $article->views = (int) ($row['views'] ?? 0);
        $article->createdAt = isset($row['created_at']) ? (string) $row['created_at'] : null;
        $article->updatedAt = isset($row['updated_at']) ? (string) $row['updated_at'] : null;

        return $article;
    }
}
