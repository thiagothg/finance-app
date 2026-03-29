<?php

use App\Http\Controllers\Api\Currency\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::get('/currencies', [CurrencyController::class, 'index']);
