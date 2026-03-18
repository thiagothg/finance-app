<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->transactionService->listTransactions($user, $request);

        return response()->json([
            'data' => $result['groups'],
            'meta' => [
                'current_page' => $result['paginator']->currentPage(),
                'last_page' => $result['paginator']->lastPage(),
                'per_page' => $result['paginator']->perPage(),
                'total' => $result['paginator']->total(),
                'accounts' => $result['accounts'],
                'categories' => $result['categories'],
                'users' => $result['users'],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request): TransactionResource
    {
        $transaction = $this->transactionService->createTransaction($request->validated());

        return new TransactionResource($transaction);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): TransactionResource
    {
        $transaction = $this->transactionService->showTransaction($transaction);

        return new TransactionResource($transaction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionRequest $request, Transaction $transaction): TransactionResource
    {
        $transaction = $this->transactionService->updateTransaction($transaction, $request->validated());

        return new TransactionResource($transaction);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): Response
    {
        $this->transactionService->deleteTransaction($transaction);

        return response()->noContent();
    }
}
