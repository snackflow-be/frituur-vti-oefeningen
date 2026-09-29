<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderingController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Staff\KitchenOrderController;
use App\Http\Controllers\Staff\KitchenOrderingController;
use App\Http\Controllers\Staff\KitchenPageController;
use App\Http\Controllers\Staff\KitchenProductController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

/*
 * Personeel: keuken (JSON-polling) en admin (Inertia-formulieren). Zie 02-architect §3.2.
 * Alles achter `auth` + personeel-middleware; elke route heeft een naam (Wayfinder).
 */
Route::middleware(['auth', EnsureStaff::class])->group(function (): void {
    // Keuken
    Route::get('/keuken', KitchenPageController::class)->name('keuken');
    Route::get('/keuken/bestellingen', [KitchenOrderController::class, 'index'])->name('keuken.orders');
    Route::patch('/keuken/bestellingen/{order}/status', [KitchenOrderController::class, 'status'])->name('keuken.orders.status');
    Route::get('/keuken/producten', [KitchenProductController::class, 'index'])->name('keuken.products');
    Route::patch('/keuken/producten/{product}/uitverkocht', [KitchenProductController::class, 'soldOut'])->name('keuken.products.soldout');
    Route::patch('/keuken/bestellen', KitchenOrderingController::class)->name('keuken.ordering');

    // Admin
    Route::prefix('admin')->name('admin')->group(function (): void {
        Route::redirect('/', '/admin/bestellingen');

        Route::name('.')->group(function (): void {
            Route::get('/bestellingen', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/bestellingen/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::patch('/bestellingen/{order}/status', [OrderController::class, 'status'])->name('orders.status');

            Route::get('/cijfers', StatsController::class)->name('stats');

            Route::get('/producten', [ProductController::class, 'index'])->name('products.index');
            Route::get('/producten/nieuw', [ProductController::class, 'create'])->name('products.create');
            Route::post('/producten', [ProductController::class, 'store'])->name('products.store');
            Route::get('/producten/{product}/bewerken', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('/producten/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/producten/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::patch('/producten/{product}/volgorde', [ProductController::class, 'move'])->name('products.move');
            Route::patch('/producten/{product}/schakelaars', [ProductController::class, 'toggle'])->name('products.toggle');

            Route::get('/categorieen', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categorieen', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('/categorieen/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categorieen/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
            Route::patch('/categorieen/{category}/volgorde', [CategoryController::class, 'move'])->name('categories.move');

            Route::get('/bestellen', [OrderingController::class, 'edit'])->name('ordering.edit');
            Route::put('/bestellen', [OrderingController::class, 'update'])->name('ordering.update');

            Route::get('/instellingen', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/instellingen', [SettingsController::class, 'update'])->name('settings.update');

            Route::get('/personeel', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/personeel', [StaffController::class, 'store'])->name('staff.store');
            Route::put('/personeel/{user}/wachtwoord', [StaffController::class, 'password'])->name('staff.password');
            Route::delete('/personeel/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');
        });
    });
});
