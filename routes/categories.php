<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Category\CategoryController;
use Illuminate\Support\Facades\Route;

Route::apiResource('categories', CategoryController::class)->names('categories');
