<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Database\Migrator;
use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

final class RepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        try {
            $pdo = Database::connection();
        } catch (Throwable $e) {
            self::markTestSkipped('MySQL is unavailable: ' . $e->getMessage());
        }

        $this->pdo = $pdo;

        (new Migrator($this->pdo, \dirname(__DIR__, 2) . '/database/migrations'))->migrate();

        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testCategoryCrud(): void
    {
        $repository = new CategoryRepository($this->pdo);

        $category = $repository->save(new Category(['name' => 'Test', 'description' => 'description']));
        self::assertNotNull($category->id);

        $loaded = $repository->find($category->id);
        self::assertNotNull($loaded);
        self::assertSame('Test', $loaded->name);
        self::assertSame('description', $loaded->description);

        $loaded->name = 'Updated';
        $repository->save($loaded);
        self::assertSame('Updated', $repository->find($category->id)?->name);

        $repository->delete($category->id);
        self::assertNull($repository->find($category->id));
    }

    public function testArticleWithCategoriesAndViews(): void
    {
        $categories = new CategoryRepository($this->pdo);
        $articles = new ArticleRepository($this->pdo);

        $first = $categories->save(new Category(['name' => 'Category A']));
        $second = $categories->save(new Category(['name' => 'Category B']));

        $article = new Article([
            'title' => 'Title',
            'description' => 'Short description',
            'text' => 'Article text',
        ]);
        $article->categories = [$first, $second];
        $articles->save($article);
        self::assertNotNull($article->id);
        self::assertNotNull($first->id);
        $articleId = $article->id;

        $loaded = $articles->find($articleId);
        self::assertNotNull($loaded);
        self::assertSame(0, $loaded->views);
        self::assertCount(2, $loaded->categories);

        $articles->incrementViews($articleId);

        $afterView = $articles->find($articleId);
        self::assertNotNull($afterView);
        self::assertSame(1, $afterView->views);

        $loaded->categories = [$first];
        $articles->save($loaded);

        $resynced = $articles->find($articleId);
        self::assertNotNull($resynced);
        self::assertSame([$first->id], $resynced->categoryIds());
    }

    public function testDeletingArticleCascadesPivot(): void
    {
        $categories = new CategoryRepository($this->pdo);
        $articles = new ArticleRepository($this->pdo);

        $category = $categories->save(new Category(['name' => 'C']));
        $article = new Article(['title' => 'A', 'text' => 'body']);
        $article->categories = [$category];
        $articles->save($article);
        self::assertNotNull($article->id);
        $articleId = $article->id;

        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM article_category WHERE article_id = ?');
        self::assertNotFalse($statement);
        $statement->execute([$articleId]);
        self::assertSame(1, (int) $statement->fetchColumn());

        $articles->delete($articleId);

        self::assertNull($articles->find($articleId));

        $statement->execute([$articleId]);
        self::assertSame(0, (int) $statement->fetchColumn());
    }
}
