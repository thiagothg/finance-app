<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HouseholdMemberRole;
use App\Http\Requests\AddHouseholdMemberRequest;
use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Http\Resources\HouseholdMemberResource;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use App\Models\User;
use App\Services\HouseholdService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class HouseholdController extends Controller
{
    public function __construct(
        private readonly HouseholdService $householdService
    ) {}

    /**
     * Display a listing of the user's households.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $households = $this->householdService->getByUser($request->user());

        return HouseholdResource::collection($households)->additional([
            'meta' => [
                'total_count' => $households->count(),
            ],
        ]);
    }

    /**
     * Store a newly created household for the user.
     */
    public function store(StoreHouseholdRequest $request): HouseholdResource
    {
        $household = $this->householdService->upsertName(
            $request->user(),
            $request->validated('name')
        );

        return new HouseholdResource($household);
    }

    /**
     * Update the specified household's name.
     */
    public function update(UpdateHouseholdRequest $request, Household $household): HouseholdResource
    {
        $household = $this->householdService->upsertName(
            $request->user(),
            $request->validated('name'),
            $household
        );

        return new HouseholdResource($household);
    }

    /**
     * Display a listing of the household's members.
     */
    public function members(Household $household): AnonymousResourceCollection
    {
        $members = $this->householdService->listMembers($household);

        return HouseholdMemberResource::collection($members);
    }

    /**
     * Add a new member to the household.
     */
    public function addMember(AddHouseholdMemberRequest $request, Household $household): Response
    {
        $userToAdd = User::findOrFail($request->validated('user_id'));

        $this->householdService->addMember(
            $household,
            $request->user(),
            $userToAdd,
            $request->enum('role', HouseholdMemberRole::class)
        );

        return response()->noContent();
    }

    /**
     * Remove a member from the household.
     */
    public function removeMember(Household $household, User $user, Request $request): Response
    {
        $this->householdService->removeMember(
            $household,
            $request->user(),
            $user
        );

        return response()->noContent();
    }
}
