<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class AccountController extends Controller
{
    public function __construct(
        private AccountService $accountService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->accountService->listAccounts($user);

        return AccountResource::collection($result['accounts'])->additional([
            'meta' => [
                'total_balance' => $result['total_balance'],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountRequest $request): AccountResource
    {
        /** @var User $user */
        $user = $request->user();

        $account = $this->accountService->createAccount($user, $request->validated());

        return new AccountResource($account);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountRequest $request, Account $account): AccountResource
    {
        Gate::authorize('update', $account);

        $account = $this->accountService->updateAccount($account, $request->validated());

        return new AccountResource($account);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account): Response
    {
        Gate::authorize('delete', $account);

        $this->accountService->deleteAccount($account);

        return response()->noContent();
    }
}
