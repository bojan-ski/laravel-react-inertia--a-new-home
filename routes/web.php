<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'title' => __('app.home.title'),
        'heading' => __('app.home.heading'),
        'subheading' => __('app.home.subheading'),
        'stack' => __('app.home.stack', [
            'app' => __('app.name'),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
        ]),
    ]);
});
