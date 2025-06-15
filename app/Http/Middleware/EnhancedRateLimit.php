<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enhanced Rate Limiting Middleware
 *
 * Implements sophisticated rate limiting for different API endpoints
 * Protects against abuse and ensures fair usage
 *
 * @author Syed Asad
 */
class EnhancedRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $key = 'api'): Response
    {
        $limiterKey = $this->resolveRequestSignature($request, $key);
        $maxAttempts = $this->getMaxAttempts($request, $key);
        $decayMinutes = $this->getDecayMinutes($key);

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($limiterKey, $maxAttempts);
        }

        RateLimiter::hit($limiterKey, $decayMinutes * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $limiterKey, $maxAttempts);
    }

    /**
     * Resolve request signature for rate limiting
     */
    private function resolveRequestSignature(Request $request, string $key): string
    {
        $userId = $request->user()?->id ?? 'guest';
        $ip = $request->ip();

        return sprintf('%s:%s:%s', $key, $userId, $ip);
    }

    /**
     * Get maximum attempts based on endpoint type
     */
    private function getMaxAttempts(Request $request, string $key): int
    {
        return match ($key) {
            'export' => 100, // Higher limit for export endpoints
            'bulk' => 20,    // Lower limit for bulk operations
            'auth' => 5,     // Very low for authentication
            default => 60,   // Standard API limit
        };
    }

    /**
     * Get decay minutes based on key
     */
    private function getDecayMinutes(string $key): int
    {
        return match ($key) {
            'export' => 1,   // 1 minute window
            'bulk' => 5,     // 5 minute window
            'auth' => 15,    // 15 minute window for auth
            default => 1,    // 1 minute standard
        };
    }

    /**
     * Build too many attempts response
     */
    private function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter,
            'limit' => $maxAttempts,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders(Response $response, string $key, int $maxAttempts): Response
    {
        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $retryAfter = RateLimiter::availableIn($key);

        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', (string) now()->addSeconds($retryAfter)->timestamp);

        return $response;
    }
}
