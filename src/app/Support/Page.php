<?php

declare(strict_types=1);

namespace App\Support;

final class Page
{
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function pages(): int
    {
        if ($this->perPage < 1) {
            return 1;
        }

        return (int) max(1, (int) ceil($this->total / $this->perPage));
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasPrev(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages();
    }
}
