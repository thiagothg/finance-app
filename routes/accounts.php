<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Account\AccountController;
use Illuminate\Support\Facades\Route;

Route::apiResource('accounts', AccountController::class)->names('accounts');
