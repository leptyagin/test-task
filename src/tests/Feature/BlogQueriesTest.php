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

final class BlogQueriesTest extends TestCase
{
    private PDO $pdo;
    private CategoryRepository $categories;
    private ArticleRepository $articles;

    protected function setUp(): void
    {
        try {
            $pdo = Database::connection();
        } catch (Throwable $e) {
            self::markTestSkipped('MySQL недоступна: ' . $e->getMessage());
        }

        $this->pdo = $pdo;
        (new Migrator($this->pdo, \dirname(__DIR__, 2) . '/database/migrations'))->migrate();
        $this->pdo->beginTransaction();

        $this->categories = new CategoryRepository($this->pdo);
        $this->articles = new ArticleRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testWithArticlesReturnsOnlyNonEmptyCategories(): void
    {
        $filled = $this->categories->save(new Category(['name' => 'Filled ' . uniqid()]));
        $empty = $this->categories->save(new Category(['name' => 'Empty ' . uniqid()]));

        $this->makeArticle('A', 1, '2026-01-01 00:00:00', [$filled]);

        $names = array_map(
            static fn (Category $c): string => $c->name,
            $this->categories->withArticles(),
        );

        self::assertContains($filled->name, $names);
        self::assertNotContains($empty->name, $names);
    }

    public function testLatestByCategoryRespectsLimitAndOrder(): void
    {
        $category = $this->categories->save(new Category(['name' => 'Cat ' . uniqid()]));
        self::assertNotNull($category->id);

        $this->makeArticle('oldest', 0, '2026-01-01 00:00:00', [$category]);
        $this->makeArticle('older', 0, '2026-02-01 00:00:00', [$category]);
        $this->makeArticle('newer', 0, '2026-03-01 00:00:00', [$category]);
        $this->makeArticle('newest', 0, '2026-04-01 00:00:00', [$category]);

        $titles = array_map(
            static fn (Article $a): string => $a->title,
            $this->articles->latestByCategory($category->id, 3),
        );

        self::assertSame(['newest', 'newer', 'older'], $titles);
    }

    public function testPaginateByCategoryPagesAndSorting(): void
    {
        $category = $this->categories->save(new Category(['name' => 'Cat ' . uniqid()]));
        self::assertNotNull($category->id);

        for ($i = 1; $i <= 6; ++$i) {
            $this->makeArticle("post {$i}", $i * 10, \sprintf('2026-01-%02d 00:00:00', $i), [$category]);
        }

        $first = $this->articles->paginateByCategory($category->id, 'views', 'desc', 1, 5);
        self::assertSame(6, $first->total);
        self::assertSame(2, $first->pages());
        self::assertCount(5, $first->items);
        self::assertSame(60, $first->items[0]->views);

        $second = $this->articles->paginateByCategory($category->id, 'views', 'desc', 2, 5);
        self::assertCount(1, $second->items);
        self::assertSame(10, $second->items[0]->views);

        $byDateAsc = $this->articles->paginateByCategory($category->id, 'date', 'asc', 1, 3);
        $titles = array_map(static fn (Article $a): string => $a->title, $byDateAsc->items);
        self::assertSame(['post 1', 'post 2', 'post 3'], $titles);
    }

    public function testSimilarSharesCategoriesAndExcludesSelf(): void
    {
        $one = $this->categories->save(new Category(['name' => 'One ' . uniqid()]));
        $two = $this->categories->save(new Category(['name' => 'Two ' . uniqid()]));
        $three = $this->categories->save(new Category(['name' => 'Three ' . uniqid()]));

        $subject = $this->makeArticle('subject', 0, '2026-01-10 00:00:00', [$one, $two]);
        $sharesBoth = $this->makeArticle('shares-both', 0, '2026-01-09 00:00:00', [$one, $two]);
        $sharesOne = $this->makeArticle('shares-one', 0, '2026-01-08 00:00:00', [$two]);
        $unrelated = $this->makeArticle('unrelated', 0, '2026-01-07 00:00:00', [$three]);

        $titles = array_map(
            static fn (Article $a): string => $a->title,
            $this->articles->similar($subject, 3),
        );

        self::assertNotContains('subject', $titles);
        self::assertNotContains($unrelated->title, $titles);
        self::assertContains($sharesBoth->title, $titles);
        self::assertContains($sharesOne->title, $titles);
        self::assertSame($sharesBoth->title, $titles[0]);
    }

    /**
     * @param list<Category> $categories
     */
    private function makeArticle(string $title, int $views, string $publishedAt, array $categories): Article
    {
        $article = new Article(['title' => $title, 'text' => 'body']);
        $article->views = $views;
        $article->publishedAt = $publishedAt;
        $article->categories = $categories;

        $this->articles->save($article);

        return $article;
    }
}
