<?php

declare(strict_types=1);

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\GasCalculationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes that require authentication via session or token.
|
*/

Route::middleware('auth')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::get('/unread', [NotificationController::class, 'unread'])->name('api.notifications.unread');
    Route::patch('/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.read-all');
    Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('api.notifications.destroy');
});

Route::prefix('aga8')->group(function () {
    Route::post('/calculate', [GasCalculationController::class, 'calculate'])->name('api.aga8.calculate');
    Route::get('/components', [GasCalculationController::class, 'components'])->name('api.aga8.components');
});
