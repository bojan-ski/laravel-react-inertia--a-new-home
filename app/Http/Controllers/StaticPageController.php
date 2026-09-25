<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class StaticPageController extends Controller
{
    public function about(): Response
    {
        return $this->render('about');
    }

    public function privacy(): Response
    {
        return $this->render('privacy');
    }

    public function terms(): Response
    {
        return $this->render('terms');
    }

    public function cookies(): Response
    {
        return $this->render('cookies');
    }

    private function render(string $page): Response
    {
        return Inertia::render('Static/Show', [
            'page' => __("pages.{$page}"),
        ]);
    }
}
