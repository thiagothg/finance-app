<?php

namespace App\Models;

use App\Enums\HouseholdMemberRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdMember extends Model
{
    /** @use HasFactory<\Database\Factories\HouseholdMemberFactory> */
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

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
