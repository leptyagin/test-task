<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Repository\ArticleRepository;

final class ArticleController extends Controller
{
    private const SIMILAR_LIMIT = 3;

    public function index(): string
    {
        return $this->view('articles', [
            'articles' => (new ArticleRepository())->all(),
        ]);
    }

    public function show(): string
    {
        $id = (int) ($_GET['id'] ?? 0);
        $repository = new ArticleRepository();
        $article = $id > 0 ? $repository->find($id) : null;

        if ($article === null || $article->id === null) {
            http_response_code(404);

            return $this->view('article', ['article' => null]);
        }

        $repository->incrementViews($article->id);
        ++$article->views;

        return $this->view('article', [
            'article' => $article,
            'similar' => $repository->similar($article, self::SIMILAR_LIMIT),
        ]);
    }
}
