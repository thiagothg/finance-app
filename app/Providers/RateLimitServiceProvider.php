<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureAuthLimiter();
        $this->configureApiLimiter();
    }

    /**
     * Strict limiter for unauthenticated auth routes.
     *
     * Applied to: POST /api/v1/auth/login, POST /api/v1/auth/register
     *
     * 10 requests per minute keyed by IP address.
     * Prevents brute-force and credential stuffing attacks.
     */
    private function configureAuthLimiter(): void
    {
        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again in a minute.',
                    ], 429);
                });
        });
    }

    /**
     * Standard limiter for all authenticated API routes.
     *
     * Applied to: all routes under auth:sanctum middleware.
     *
     * 60 requests per minute keyed by authenticated user ID.
     * Falls back to IP address for unauthenticated requests that
     * slip through (should not happen in practice).
     */
    private function configureApiLimiter(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please slow down.',
                    ], 429);
                });
        });
    }
}
