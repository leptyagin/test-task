<?php

declare(strict_types=1);

namespace App\Entity;

final class Article extends Entity
{
    public ?int $id = null;
    public ?string $image = null;
    public string $title = '';
    public ?string $description = null;
    public string $text = '';
    public int $views = 0;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    /** @var list<Category> */
    public array $categories = [];

    /**
     * @return list<int>
     */
    public function categoryIds(): array
    {
        $ids = [];

        foreach ($this->categories as $category) {
            if ($category->id !== null) {
                $ids[] = $category->id;
            }
        }

        return $ids;
    }
}
