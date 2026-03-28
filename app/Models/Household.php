<?php

namespace App\Models;

use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read HouseholdMember|null $currentUserMembership
 * @property-read Collection<int, HouseholdMember> $members
 * @property-read User $owner
 */
final class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'owner_id',
        'invitation_code',
    ];

    protected static function booted(): void
    {
        self::creating(function (Household $household) {
            $household->invitation_code = $household->invitation_code ?? str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        });
    }

    /*** Relations ***/

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<HouseholdMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    /** @return HasOne<HouseholdMember, $this> */
    public function currentUserMembership(): HasOne
    {
        return $this->hasOne(HouseholdMember::class);
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
