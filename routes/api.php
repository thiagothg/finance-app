<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user/me', fn (Request $request) => $request->user());

    require __DIR__.'/households.php';
    require __DIR__.'/categories.php';
    require __DIR__.'/accounts.php';
    require __DIR__.'/transactions.php';
});

Route::get('/health', fn () => response()->json(['status' => 'ok']));
