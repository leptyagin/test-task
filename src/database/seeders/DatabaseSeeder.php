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

    $categoryRepository = new CategoryRepository($pdo);
    $articleRepository = new ArticleRepository($pdo);

    $php = $categoryRepository->save(new Category([
        'name' => 'PHP',
        'description' => 'Materials about the PHP language',
    ]));
    $architecture = $categoryRepository->save(new Category([
        'name' => 'Architecture',
        'description' => 'Patterns and approaches to design',
    ]));
    $databases = $categoryRepository->save(new Category([
        'name' => 'Databases',
        'description' => 'SQL, migrations, indexes',
    ]));
    
    $categoryRepository->save(new Category([
        'name' => 'Empty',
        'description' => 'A category without articles',
    ]));

    $blueprints = [
        ['PHP', 'Repository pattern', [$php, $architecture]],
        ['PHP', 'Autoloading with Composer', [$php]],
        ['PHP', 'Strict types and type hints', [$php]],
        ['PHP', 'Template engine: Smarty', [$php]],
        ['PHP', 'Front controller', [$php, $architecture]],
        ['PHP', 'Dependency injection basics', [$php, $architecture]],
        ['ARCH', 'Layered MVC with entities', [$architecture]],
        ['ARCH', 'Schema migrations', [$architecture, $databases]],
        ['ARCH', 'Value objects', [$architecture]],
        ['DB', 'Indexes that matter', [$databases]],
        ['DB', 'Many-to-many relations', [$databases, $php]],
        ['DB', 'Pagination in SQL', [$databases]],
    ];

    $day = 0;

    foreach ($blueprints as [$prefix, $title, $categories]) {
        ++$day;

        $article = new Article([
            'image' => 'https://placehold.co/600x400?text=' . rawurlencode($prefix),
            'title' => $title,
            'description' => $title . ' — a short summary of the article.',
            'text' => $title . "\n\n" . str_repeat('Sample paragraph for the demo content. ', 12),
        ]);
        $article->views = ($day * 37) % 500;
        $article->publishedAt = (new DateTimeImmutable("-{$day} days"))->format('Y-m-d H:i:s');
        $article->categories = $categories;

        $articleRepository->save($article);
    }
};
