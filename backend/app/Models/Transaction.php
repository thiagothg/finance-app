<?php

namespace App\Models;

use App\Enums\TransactionType;
use Carbon\Carbon;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $account_id
 * @property int|null $category_id
 * @property int $spender_user_id
 * @property float|string $amount
 * @property TransactionType $type
 * @property string|null $description
 * @property Carbon $transaction_at
 * @property int|null $to_account_id
 * @property string $currency
 * @property float|string|null $amount_base
 * @property float|string|null $exchange_rate
 */
final class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'category_id',
        'spender_user_id',
        'amount',
        'type',
        'description',
        'transaction_at',
        'to_account_id',
        'currency',
        'amount_base',
        'exchange_rate',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'transaction_at' => 'datetime',
        ];
    }

    /*** Relations ***/

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function spender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spender_user_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
