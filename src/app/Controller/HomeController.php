<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(): string
    {
        return $this->view('home', [
            'title'   => 'title',
        ]);
    }
}
