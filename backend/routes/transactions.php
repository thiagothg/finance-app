<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('transactions', TransactionController::class)->names('transactions');
