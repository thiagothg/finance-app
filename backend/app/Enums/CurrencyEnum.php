<?php

declare(strict_types=1);

namespace App\Enums;

enum CurrencyEnum: string
{
    case BRL = 'BRL';
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case ARS = 'ARS';
    case CLP = 'CLP';
    case CAD = 'CAD';
    case JPY = 'JPY';
}
