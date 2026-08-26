<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entity\Category;
use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    public function testFillMapsSnakeCaseKeysToCamelCaseProperties(): void
    {
        $category = new Category([
            'id' => 42,
            'name' => 'PHP',
            'description' => 'about php',
            'created_at' => '2026-08-26 12:00:00',
        ]);

        self::assertSame(42, $category->id);
        self::assertSame('PHP', $category->name);
        self::assertSame('about php', $category->description);
        self::assertSame('2026-08-26 12:00:00', $category->createdAt);
    }

    public function testFillIgnoresUnknownKeys(): void
    {
        $category = new Category(['name' => 'PHP', 'nonexistent' => 'x']);

        self::assertSame('PHP', $category->name);
        self::assertArrayNotHasKey('nonexistent', $category->toArray());
    }

    public function testToArrayReturnsAllProperties(): void
    {
        $category = new Category(['id' => 1, 'name' => 'PHP']);

        self::assertSame(
            [
                'id' => 1,
                'name' => 'PHP',
                'description' => null,
                'createdAt' => null,
                'updatedAt' => null,
            ],
            $category->toArray(),
        );
    }
}
