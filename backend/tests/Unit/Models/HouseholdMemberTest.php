<?php

use App\Enums\HouseholdMemberRole;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\User;

it('can create a household member', function () {
    $member = HouseholdMember::factory()->create();
    expect($member)->toBeInstanceOf(HouseholdMember::class);
    expect($member->role)->toBeInstanceOf(HouseholdMemberRole::class);
});

it('belongs to a household and user', function () {
    $member = HouseholdMember::factory()->create();
    expect($member->household)->toBeInstanceOf(Household::class);
    expect($member->user)->toBeInstanceOf(User::class);
});
