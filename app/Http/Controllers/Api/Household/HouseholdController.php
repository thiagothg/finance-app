<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Household;

use App\Enums\HouseholdMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddHouseholdMemberRequest;
use App\Http\Requests\JoinHouseholdRequest;
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
        private HouseholdService $householdService
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
     * Add a new member to the household by invitation.
     */
    public function addMember(AddHouseholdMemberRequest $request, Household $household): Response
    {
        $this->householdService->addMember(
            $household,
            $request->user(),
            $request->validated('name'),
            $request->validated('email'),
            $request->enum('role', HouseholdMemberRole::class)
        );

        return response()->noContent();
    }

    /**
     * Resend a pending invitation to a household member.
     */
    public function resendInvitation(Household $household, User $user, Request $request): Response
    {
        $this->householdService->resendInvitation(
            $household,
            $request->user(),
            $user
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

    /**
     * Join a household using an invitation code.
     */
    public function join(JoinHouseholdRequest $request): HouseholdResource
    {
        $household = $this->householdService->joinByCode(
            $request->user(),
            $request->validated('invitation_code')
        );

        return new HouseholdResource($household);
    }

    /**
     * Accept a pending household invitation.
     */
    public function acceptInvitation(Request $request, string $household): HouseholdResource
    {
        $acceptedHousehold = $this->householdService->acceptInvitation(
            $request->user(),
            $household
        );

        return new HouseholdResource($acceptedHousehold);
    }

    /**
     * Decline a pending household invitation.
     */
    public function declineInvitation(Request $request, string $household): Response
    {
        $this->householdService->declineInvitation(
            $request->user(),
            $household
        );

        return response()->noContent();
    }
}
