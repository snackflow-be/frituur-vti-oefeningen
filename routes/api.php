<?php

use App\Http\Controllers\Api\ApiDocsController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// Publieke API (stateless, geen sessie of CSRF). Middleware-groep `api` met throttle 600/min per IP
// (zie AppServiceProvider); de rem op bestellen zelf zit in PlaceOrder (per IP en per gsm-nummer).
Route::get('/menu', MenuController::class)->name('api.menu');
Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
// Geen route-constraint op de token: elke onbekende waarde (ook met `-`/`_` of te lang) krijgt
// dezelfde 404 "Bestelling niet gevonden." uit de controller, net als de webroutes.
Route::get('/orders/{token}', [OrderController::class, 'show'])->name('api.orders.show');
Route::get('/docs', ApiDocsController::class)->name('api.docs');
