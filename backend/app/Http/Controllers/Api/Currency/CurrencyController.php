<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Currency;

use App\Enums\CurrencyEnum;
use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    public function index(): JsonResponse
    {
        $rates = $this->exchangeRateService->getRatesFor(CurrencyEnum::BRL);

        $data = [];
        foreach (CurrencyEnum::cases() as $currency) {
            // Because the frontend receives these from BRL,
            // the rate to BRL is basically the value returned from the API, since the base is BRL.
            // Oh wait, if the base is BRL, the API returns 1 BRL = X Currency
            $rate = $rates[$currency->value] ?? null;

            $data[] = [
                'code' => $currency->value,
                'label' => $this->getLabel($currency),
                'symbol' => $this->getSymbol($currency),
                'decimals' => $currency === CurrencyEnum::JPY ? 0 : 2,
                'rate_to_brl' => $rate !== null ? round(1 / $rate, 6) : null,
            ];
        }

        return response()->json(['data' => $data]);
    }

    private function getLabel(CurrencyEnum $currency): string
    {
        return match ($currency) {
            CurrencyEnum::BRL => 'Brazilian Real',
            CurrencyEnum::USD => 'US Dollar',
            CurrencyEnum::EUR => 'Euro',
            CurrencyEnum::GBP => 'British Pound',
            CurrencyEnum::ARS => 'Argentine Peso',
            CurrencyEnum::CLP => 'Chilean Peso',
            CurrencyEnum::CAD => 'Canadian Dollar',
            CurrencyEnum::JPY => 'Japanese Yen',
        };
    }

    private function getSymbol(CurrencyEnum $currency): string
    {
        return match ($currency) {
            CurrencyEnum::BRL => 'R$',
            CurrencyEnum::USD => '$',
            CurrencyEnum::EUR => '€',
            CurrencyEnum::GBP => '£',
            CurrencyEnum::ARS, CurrencyEnum::CLP, CurrencyEnum::CAD => '$',
            CurrencyEnum::JPY => '¥',
        };
    }
}
