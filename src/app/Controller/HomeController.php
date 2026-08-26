<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

final class HomeController extends Controller
{
    private const LATEST_PER_CATEGORY = 3;

    public function index(): string
    {
        $categories = new CategoryRepository();
        $articles = new ArticleRepository();

        $blocks = [];

        foreach ($categories->withArticles() as $category) {
            if ($category->id === null) {
                continue;
            }

            $blocks[] = [
                'category' => $category,
                'articles' => $articles->latestByCategory($category->id, self::LATEST_PER_CATEGORY),
            ];
        }

        return $this->view('home', ['blocks' => $blocks]);
    }
}
