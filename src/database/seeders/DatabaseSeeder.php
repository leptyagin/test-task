<?php

declare(strict_types=1);

use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

return static function (PDO $pdo): void {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE article_category');
    $pdo->exec('TRUNCATE articles');
    $pdo->exec('TRUNCATE categories');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $categories = new CategoryRepository($pdo);
    $articles = new ArticleRepository($pdo);

    $php = $categories->save(new Category([
        'name' => 'PHP',
        'description' => 'Materials about the PHP language',
    ]));
    $architecture = $categories->save(new Category([
        'name' => 'Architecture',
        'description' => 'Patterns and approaches to design',
    ]));
    $categories->save(new Category([
        'name' => 'Databases',
        'description' => 'SQL, migrations, indexes',
    ]));

    $repositoryPattern = new Article([
        'image' => 'https://placehold.co/600x400?text=Repository',
        'title' => 'The Repository Pattern',
        'description' => 'Separating data access from domain entities',
        'text' => "The repository encapsulates queries to the storage and returns entities.\n"
            . 'The entity remains a clean data carrier, while all database work lives in the repository.',
    ]);
    $repositoryPattern->categories = [$php, $architecture];
    $articles->save($repositoryPattern);

    $migrations = new Article([
        'image' => 'https://placehold.co/600x400?text=Migrations',
        'title' => 'Database Schema Migrations',
        'description' => 'Versioning the structure of the database',
        'text' => 'Each migration is a step forward (up) and a step back (down). '
            . 'The runner applies missing migrations and records them in the migrations table.',
    ]);
    $migrations->categories = [$architecture];
    $articles->save($migrations);
};
