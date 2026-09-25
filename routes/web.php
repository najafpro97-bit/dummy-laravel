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

Route::get('/health', function () {
    return response()->json([
        'status'  => 'ok',
        'app'     => config('app.name'),
        'env'     => app()->environment(),
        'php'     => PHP_VERSION,
        'laravel' => app()->version(),
        'host'    => gethostname(),
        'time'    => now()->toIso8601String(),
    ]);
})->name('health');
