<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Simple dummy test site routes.
|
*/

Route::get('/', function () {
    return view('home', [
        'title'    => 'Home',
        'features' => [
            'Built on Laravel ' . app()->version(),
            'Running PHP ' . PHP_VERSION,
            'Served from ' . gethostname(),
            'Current time (server): ' . now()->format('Y-m-d H:i:s'),
        ],
    ]);
})->name('home');

Route::get('/about', function () {
    return view('about', ['title' => 'About']);
})->name('about');

// NOTE: /health lives in routes/health.php so it runs without session
// middleware and keeps working when the database is down.
