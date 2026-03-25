<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CurrencyEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class ExchangeRateService
{
    /**
     * @return array<string, float> array of rate multipliers (1 BRL = X Currency)
     */
    public function getRatesFor(CurrencyEnum $baseCurrency = CurrencyEnum::BRL): array
    {
        $cacheKey = "exchange_rates:{$baseCurrency->value}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($baseCurrency) {
            $apiKey = config('services.exchange_rate.key');

            if (empty($apiKey)) {
                // Return 1:1 fallback for all supported currencies when API key is missing
                $fallback = [];
                foreach (CurrencyEnum::cases() as $currency) {
                    $fallback[$currency->value] = 1.0;
                }
                return $fallback;
            }

            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency->value}");

            if ($response->failed()) {
                throw new RuntimeException("Failed to fetch exchange rates from API: {$response->body()}");
            }

            $data = $response->json();

            if (($data['result'] ?? '') !== 'success') {
                throw new RuntimeException("Exchange API returned error: " . ($data['error-type'] ?? 'Unknown'));
            }

            return $data['conversion_rates'] ?? [];
        });
    }

    public function forgetCache(CurrencyEnum $baseCurrency = CurrencyEnum::BRL): void
    {
        Cache::forget("exchange_rates:{$baseCurrency->value}");
    }
}
