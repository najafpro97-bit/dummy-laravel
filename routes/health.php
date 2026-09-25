<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Routes
|--------------------------------------------------------------------------
|
| Registered WITHOUT the "web" middleware group, so no session is started and
| no cookie is encrypted. These are polled by uptime monitors and must keep
| working even when the database (which backs sessions) is unreachable.
|
*/

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
