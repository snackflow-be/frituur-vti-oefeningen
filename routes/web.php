<?php

use App\Http\Controllers\HealthController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Inertia\Inertia;

// Klantpagina's (02-architect §4): Inertia-schillen die de publieke JSON-API aanroepen.
Route::get('/', function () {
    // Sfeerfoto enkel doorgeven als ze bestaat: zo vraagt de browser nooit een 404 op.
    $hero = collect(['webp', 'jpg'])
        ->map(fn (string $ext) => "/img/hero.$ext")
        ->first(fn (string $path) => file_exists(public_path($path)));

    return Inertia::render('menu/index', ['heroUrl' => $hero]);
})->name('home');
Route::inertia('/bestellen', 'bestellen/index')->name('bestellen');

Route::get('/bevestiging/{token}', fn (string $token) => Inertia::render('bevestiging/index', ['token' => $token]))
    ->where('token', '[A-Za-z0-9_-]{1,64}')
    ->name('bevestiging');

Route::get('/bestelling/{token}', fn (string $token) => Inertia::render('opvolgen/index', ['token' => $token]))
    ->where('token', '[A-Za-z0-9_-]{1,64}')
    ->name('opvolgen');

// Gezondheidscontrole voor de uitrol (zie HealthController): één vast adres,
// zonder sessie of cookies. De uitrol vergelijkt de release en rolt terug bij een fout.
Route::get('/health', HealthController::class)
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        PreventRequestForgery::class,
    ])
    ->name('health');

// Oude landingsplek uit het template: personeel landt op het keukenscherm.
// De naam blijft bestaan zodat Wayfinder (`dashboard()` in app-sidebar/app-header) blijft compileren.
Route::middleware('auth')->group(function () {
    Route::get('dashboard', fn () => redirect('/keuken'))->name('dashboard');
});

require __DIR__.'/settings.php';

// Keuken en admin (rol personeel); bestaat het bestand nog niet, dan draait de klantsite gewoon.
if (file_exists(__DIR__.'/staff.php')) {
    require __DIR__.'/staff.php';
}
