<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/me', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('households', HouseholdController::class)->except(['show', 'destroy']);
    Route::get('/households/{household}/members', [HouseholdController::class, 'members'])->name('households.members.index');
    Route::post('/households/{household}/members', [HouseholdController::class, 'addMember'])->name('households.members.store');
    Route::delete('/households/{household}/members/{user}', [HouseholdController::class, 'removeMember'])->name('households.members.destroy');

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('transactions', TransactionController::class);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
