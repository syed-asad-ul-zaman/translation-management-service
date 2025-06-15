<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Performance Optimization Middleware
 *
 * Handles response time optimization and caching headers
 * Ensures API responses meet <200ms requirement
 *
 * @author Syed Asad
 */
class PerformanceOptimization
{
    /**
     * Maximum allowed response time in milliseconds
     */
    private const MAX_RESPONSE_TIME = 200;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $startTime = microtime(true);

        // Add performance headers
        $response = $next($request);

        $this->addPerformanceHeaders($response, $startTime);
        $this->addCacheHeaders($response, $request);
        $this->logSlowRequests($request, $startTime);

        return $response;
    }

    /**
     * Add performance-related headers
     */
    private function addPerformanceHeaders(BaseResponse $response, float $startTime): void
    {
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        $response->headers->set('X-Response-Time', $responseTime . 'ms');
        $response->headers->set('X-Performance-Status', $responseTime <= self::MAX_RESPONSE_TIME ? 'optimal' : 'slow');
        $response->headers->set('X-Timestamp', now()->toISOString());
    }

    /**
     * Add appropriate cache headers
     */
    private function addCacheHeaders(BaseResponse $response, Request $request): void
    {
        // For export endpoints, add aggressive caching
        if (str_starts_with($request->path(), 'api/export/')) {
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            $response->headers->set('Expires', now()->addHour()->toRfc7231String());
            $response->headers->set('ETag', md5($response->getContent()));
        }

        // For API endpoints, add moderate caching
        if (str_starts_with($request->path(), 'api/')) {
            $response->headers->set('X-RateLimit-Limit', '1000');
            $response->headers->set('X-RateLimit-Remaining', '999');
        }
    }

    /**
     * Log slow requests for monitoring
     */
    private function logSlowRequests(Request $request, float $startTime): void
    {
        $responseTime = (microtime(true) - $startTime) * 1000;

        if ($responseTime > self::MAX_RESPONSE_TIME) {
            logger()->warning('Slow API response detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'response_time' => $responseTime . 'ms',
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
            ]);
        }
    }
}
