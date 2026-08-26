<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Page;
use PHPUnit\Framework\TestCase;

final class PageTest extends TestCase
{
    public function testPagesCountRoundsUp(): void
    {
        $page = new Page([], 23, 1, 5);

        self::assertSame(5, $page->pages());
    }

    public function testPagesCountIsAtLeastOne(): void
    {
        self::assertSame(1, (new Page([], 0, 1, 5))->pages());
    }

    public function testOffset(): void
    {
        self::assertSame(0, (new Page([], 23, 1, 5))->offset());
        self::assertSame(10, (new Page([], 23, 3, 5))->offset());
    }

    public function testHasPrevAndNext(): void
    {
        $middle = new Page([], 23, 3, 5);
        self::assertTrue($middle->hasPrev());
        self::assertTrue($middle->hasNext());

        $first = new Page([], 23, 1, 5);
        self::assertFalse($first->hasPrev());
        self::assertTrue($first->hasNext());

        $last = new Page([], 23, 5, 5);
        self::assertTrue($last->hasPrev());
        self::assertFalse($last->hasNext());
    }
}
