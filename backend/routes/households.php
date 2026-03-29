<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Household\HouseholdController;
use Illuminate\Support\Facades\Route;

Route::post('households/{household}/members/accept', [HouseholdController::class, 'acceptInvitation'])
    ->where('household', '[0-9]{8}')
    ->name('households.members.accept');
Route::post('households/{household}/members/decline', [HouseholdController::class, 'declineInvitation'])
    ->where('household', '[0-9]{8}')
    ->name('households.members.decline');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('households/join', [HouseholdController::class, 'join'])->name('households.join');

    Route::apiResource('households', HouseholdController::class)
        ->except(['show', 'destroy'])
        ->names('households');

    Route::prefix('households/{household}')->group(function (): void {
        Route::get('/members', [HouseholdController::class, 'members'])->name('households.members.index');
        Route::post('/members', [HouseholdController::class, 'addMember'])->name('households.members.store');
        Route::post('/members/{user}/resend-invitation', [HouseholdController::class, 'resendInvitation'])->name('households.members.resend-invitation');
        Route::delete('/members/{user}', [HouseholdController::class, 'removeMember'])->name('households.members.destroy');
    });

});
