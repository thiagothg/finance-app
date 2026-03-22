<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Household\HouseholdController;
use Illuminate\Support\Facades\Route;

Route::apiResource('households', HouseholdController::class)
     ->except(['show', 'destroy'])
     ->names('households');

Route::prefix('households/{household}')->group(function (): void {
    Route::get('/members', [HouseholdController::class, 'members'])->name('households.members.index');
    Route::post('/members', [HouseholdController::class, 'addMember'])->name('households.members.store');
    Route::delete('/members/{user}', [HouseholdController::class, 'removeMember'])->name('households.members.destroy');
});
