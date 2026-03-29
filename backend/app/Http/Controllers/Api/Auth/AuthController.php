<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendValidationCodeRequest;
use App\Http\Requests\ValidateCodeRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Register a new user and return a token pair.
     */
    public function register(RegisterRequest $request): AuthResource
    {
        $result = $this->authService->register($request->validated());

        return new AuthResource($result);
    }

    /**
     * Authenticate, generate a verification code, and email it to the user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => $result['message'],
            'email' => $result['user']->email,
            'verification_expires_at' => $result['verification_expires_at']->toIso8601String(),
        ]);
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Exchange a valid refresh token for a new token pair.
     *
     * This endpoint does NOT use auth:sanctum middleware — the refresh token
     * is passed in the request body and validated manually inside AuthService.
     * This allows the Flutter app to call it even after the access token expires.
     */
    public function refresh(RefreshTokenRequest $request): AuthResource
    {
        $result = $this->authService->refresh($request->input('refresh_token'));

        return new AuthResource($result);
    }

    /**
     * Send a validation code to the user's email.
     */
    public function sendValidationCode(SendValidationCodeRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $this->authService->generateValidationCode($user);

        return response()->json(['message' => 'Validation code sent.']);
    }

    /**
     * Resend a validation code to the user's email.
     */
    public function resendValidationCode(SendValidationCodeRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $this->authService->generateValidationCode($user);

        return response()->json(['message' => 'Validation code resent.']);
    }

    /**
     * Verify a validation code and return a token pair.
     */
    public function validateCode(ValidateCodeRequest $request): AuthResource
    {
        $result = $this->authService->verifyValidationCode(
            $request->validated('email'),
            $request->validated('code')
        );

        return new AuthResource($result);
    }
}
