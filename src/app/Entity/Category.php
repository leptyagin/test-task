<?php

declare(strict_types=1);

namespace App\Entity;

final class Category extends Entity
{
    public ?int $id = null;
    public string $name = '';
    public ?string $description = null;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;
}
