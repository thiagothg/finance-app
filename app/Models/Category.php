<?php

namespace App\Models;

use App\Enums\CategoryType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property CategoryType $type
 * @property string|null $icon
 * @property string|null $color
 * @property string|null $budget
 * @property int $user_id
 * @property int $household_id
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'household_id',
        'user_id',
        'name',
        'type',
        'icon',
        'color',
        'budget',
    ];

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'budget' => 'decimal:2',
        ];
    }

    /*** Relations ***/

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
