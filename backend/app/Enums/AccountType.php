<?php

namespace App\Enums;

enum AccountType: string
{
    case Checking = 'Checking';
    case Savings = 'Savings';
    case Cash = 'Cash';
}
