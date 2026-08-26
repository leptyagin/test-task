<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

final class CategoryController extends Controller
{
    private const PER_PAGE = 5;

    /** @var list<string> */
    private const SORTS = ['date', 'views'];

    public function index(): string
    {
        return $this->view('categories', [
            'categories' => (new CategoryRepository())->all(),
        ]);
    }

    public function show(): string
    {
        $id = (int) ($_GET['id'] ?? 0);
        $category = $id > 0 ? (new CategoryRepository())->find($id) : null;

        if ($category === null || $category->id === null) {
            http_response_code(404);

            return $this->view('category', ['category' => null]);
        }

        $sort = $this->param('sort');
        $sort = \in_array($sort, self::SORTS, true) ? $sort : 'date';
        $dir = $this->param('dir') === 'asc' ? 'asc' : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = (new ArticleRepository())->paginateByCategory(
            $category->id,
            $sort,
            $dir,
            $page,
            self::PER_PAGE,
        );

        return $this->view('category', [
            'category' => $category,
            'page' => $result,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    private function param(string $name): string
    {
        $value = $_GET[$name] ?? null;

        return \is_string($value) ? $value : '';
    }
}
