<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entity\Article;
use App\Entity\Category;
use PHPUnit\Framework\TestCase;

final class ArticleTest extends TestCase
{
    public function testDefaults(): void
    {
        $article = new Article(['title' => 'T', 'text' => 'body']);

        self::assertSame(0, $article->views);
        self::assertNull($article->image);
        self::assertSame([], $article->categories);
        self::assertSame([], $article->categoryIds());
    }

    public function testCategoryIdsSkipsCategoriesWithoutId(): void
    {
        $article = new Article(['title' => 'T', 'text' => 'body']);
        $article->categories = [
            new Category(['id' => 3, 'name' => 'A']),
            new Category(['name' => 'B']),
            new Category(['id' => 7, 'name' => 'C']),
        ];

        self::assertSame([3, 7], $article->categoryIds());
    }
}
