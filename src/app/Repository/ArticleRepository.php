<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Support\Page;
use PDO;

final class ArticleRepository extends Repository
{
    private const COLUMNS = 'id, image, title, description, text, views, published_at, created_at, updated_at';

    private const SORTS = [
        'date' => 'a.published_at',
        'views' => 'a.views',
    ];

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
        return $this->hydrateAll(
            $this->query(
                'SELECT ' . self::COLUMNS . ' FROM articles ORDER BY published_at DESC, id DESC',
            ),
        );
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
            $article->publishedAt ??= date('Y-m-d H:i:s');

            if ($article->id === null) {
                $statement = $this->prepare(
                    'INSERT INTO articles (image, title, description, text, views, published_at)
                     VALUES (:image, :title, :description, :text, :views, :published_at)',
                );
                $statement->execute($this->params($article));
                $article->id = (int) $this->pdo->lastInsertId();
            } else {
                $statement = $this->prepare(
                    'UPDATE articles
                     SET image = :image, title = :title, description = :description,
                         text = :text, views = :views, published_at = :published_at
                     WHERE id = :id',
                );
                $statement->execute($this->params($article) + ['id' => $article->id]);
            }

            $this->syncCategories($article);

            return $article;
        });
    }

    /**
     * Последние статьи категории (по дате публикации). Для главной страницы.
     *
     * @return list<Article>
     */
    public function latestByCategory(int $categoryId, int $limit): array
    {
        $limit = max(1, $limit);

        $statement = $this->prepare(
            'SELECT ' . $this->prefixed('a') . '
             FROM articles a
             JOIN article_category ac ON ac.article_id = a.id
             WHERE ac.category_id = :cat
             ORDER BY a.published_at DESC, a.id DESC
             LIMIT ' . $limit,
        );
        $statement->execute(['cat' => $categoryId]);

        return $this->hydrateAll($statement);
    }

    /**
     * Статьи категории с сортировкой и постраничной навигацией. Для страницы категории.
     *
     * @param string $sort одно из ключей self::SORTS ('date' | 'views')
     * @param string $dir  'asc' | 'desc'
     *
     * @return Page<Article>
     */
    public function paginateByCategory(int $categoryId, string $sort, string $dir, int $page, int $perPage): Page
    {
        $orderColumn = self::SORTS[$sort] ?? self::SORTS['date'];
        $orderDir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $count = $this->prepare('SELECT COUNT(*) FROM article_category WHERE category_id = :cat');
        $count->execute(['cat' => $categoryId]);
        $total = (int) $count->fetchColumn();

        $statement = $this->prepare(
            'SELECT ' . $this->prefixed('a') . '
             FROM articles a
             JOIN article_category ac ON ac.article_id = a.id
             WHERE ac.category_id = :cat
             ORDER BY ' . $orderColumn . ' ' . $orderDir . ', a.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
        );
        $statement->execute(['cat' => $categoryId]);

        return new Page($this->hydrateAll($statement), $total, $page, $perPage);
    }

    /**
     * Похожие статьи: делят категории с заданной, отсортированы по числу общих категорий.
     *
     * @return list<Article>
     */
    public function similar(Article $article, int $limit): array
    {
        $categoryIds = $article->categoryIds();

        if ($categoryIds === [] || $article->id === null) {
            return [];
        }

        $limit = max(1, $limit);
        $placeholders = implode(', ', array_fill(0, \count($categoryIds), '?'));

        $statement = $this->prepare(
            'SELECT ' . $this->prefixed('a') . ', COUNT(*) AS shared
             FROM articles a
             JOIN article_category ac ON ac.article_id = a.id
             WHERE ac.category_id IN (' . $placeholders . ') AND a.id <> ?
             GROUP BY a.id
             ORDER BY shared DESC, a.published_at DESC, a.id DESC
             LIMIT ' . $limit,
        );

        $params = $categoryIds;
        $params[] = $article->id;
        $statement->execute($params);

        return $this->hydrateAll($statement);
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
            'published_at' => $article->publishedAt,
        ];
    }

    /**
     * @return list<Article>
     */
    private function hydrateAll(\PDOStatement $statement): array
    {
        $articles = [];

        foreach ($statement->fetchAll() as $row) {
            /** @var array<string, mixed> $row */
            $articles[] = $this->hydrate($row);
        }

        $this->attachCategories($articles);

        return $articles;
    }

    private function prefixed(string $alias): string
    {
        return $alias . '.' . str_replace(', ', ', ' . $alias . '.', self::COLUMNS);
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
        $article->publishedAt = isset($row['published_at']) ? (string) $row['published_at'] : null;
        $article->createdAt = isset($row['created_at']) ? (string) $row['created_at'] : null;
        $article->updatedAt = isset($row['updated_at']) ? (string) $row['updated_at'] : null;

        return $article;
    }
}
