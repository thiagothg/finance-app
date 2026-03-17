<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Household;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $household = $user->household()->first();

        // Get all member IDs of the household this user belongs to
        // Because HouseholdMember connects users to households, we can fetch users via the household relation
        $memberIds = [];
        if ($household instanceof Household) {
            $memberIds = $household->members()->pluck('user_id')->toArray();
        } else {
            $memberIds = [$user->id];
        }

        $accounts = Account::query()->with('user')
            ->whereIn('user_id', $memberIds)
            ->get();

        $totalSum = (float) $accounts->sum(function ($account) {
            // Note: If account is new/unused, it may not have balance set (default 0), so we fallback to initial_balance logic if needed.
            return (float) ($account->balance != 0 ? $account->balance : $account->initial_balance);
        });

        return AccountResource::collection($accounts)->additional([
            'meta' => [
                'total_balance' => round($totalSum, 2),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountRequest $request): AccountResource
    {
        $validated = $request->validated();

        // If user_id is not provided, default to authenticated user
        if (! isset($validated['user_id'])) {
            $validated['user_id'] = $request->user()->id;
        }

        // The policy will authorize this action (creating for self or others)
        $account = Account::query()->create($validated);

        $account->load('user');

        return new AccountResource($account);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountRequest $request, Account $account): AccountResource
    {
        Gate::authorize('update', $account);

        $account->update($request->validated());

        $account->load('user');

        return new AccountResource($account);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return mixed
     */
    public function destroy(Account $account): Response
    {
        Gate::authorize('delete', $account);

        $account->delete();

        return response()->noContent();
    }
}
