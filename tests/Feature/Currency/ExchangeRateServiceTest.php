<?php

declare(strict_types=1);

use App\Enums\CurrencyEnum;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function getCleanExchangeRateService(): ExchangeRateService
{
    $service = app(ExchangeRateService::class);
    $service->forgetCache(CurrencyEnum::BRL);
    
    return $service;
}

it('returns 1:1 fallback rates when API key is missing', function () {
    config(['services.exchange_rate.key' => null]);

    $service = getCleanExchangeRateService();
    $rates = $service->getRatesFor(CurrencyEnum::BRL);

    expect($rates['USD'])->toBe(1.0)
        ->and($rates['EUR'])->toBe(1.0)
        ->and($rates['BRL'])->toBe(1.0);

    Http::assertNothingSent();
});

it('fetches rates from api and caches them', function () {
    config(['services.exchange_rate.key' => 'test_api_key']);

    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'conversion_rates' => [
                'BRL' => 1.0,
                'USD' => 0.18,
                'EUR' => 0.17,
            ],
        ]),
    ]);

    $service = getCleanExchangeRateService();
    $rates = $service->getRatesFor(CurrencyEnum::BRL);

    expect($rates)->toHaveKey('USD')
        ->and($rates['USD'])->toBe(0.18);

    // Call again to ensure cache is hit
    $service->getRatesFor(CurrencyEnum::BRL);

    Http::assertSentCount(1);
    
    expect(Cache::has("exchange_rates:" . CurrencyEnum::BRL->value))->toBeTrue();
});

it('throws runtime exception if api returns error', function () {
    config(['services.exchange_rate.key' => 'test_api_key']);

    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'error',
            'error-type' => 'invalid-key',
        ]),
    ]);

    $service = getCleanExchangeRateService();
    $service->getRatesFor(CurrencyEnum::BRL);
})->throws(RuntimeException::class, 'Exchange API returned error: invalid-key');

it('throws runtime exception if http request fails', function () {
    config(['services.exchange_rate.key' => 'test_api_key']);

    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response(['message' => 'Internal Error'], 500),
    ]);

    $service = getCleanExchangeRateService();
    $service->getRatesFor(CurrencyEnum::BRL);
})->throws(RuntimeException::class);
