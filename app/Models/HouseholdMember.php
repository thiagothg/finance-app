<?php

namespace App\Models;

use App\Enums\HouseholdMemberRole;
use Database\Factories\HouseholdMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int $user_id
 * @property HouseholdMemberRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Household $household
 * @property-read User $user
 */
final class HouseholdMember extends Model
{
    /** @use HasFactory<HouseholdMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'household_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => HouseholdMemberRole::class,
        ];
    }

    /*** Relations ***/

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
