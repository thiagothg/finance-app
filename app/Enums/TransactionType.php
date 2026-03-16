<?php

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'Income';
    case Expense = 'Expense';
    case Transfer = 'Transfer';
}
