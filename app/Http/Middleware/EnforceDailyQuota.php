<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnforceDailyQuota
{
    public function handle(Request $request, Closure $next, string $bucket): Response
    {
        if ($bucket === 'public_search' && !$request->filled('search')) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        /** @var array{per_actor?: int, global?: int}|null $quotaConfig */
        $quotaConfig = config("security.daily_quotas.{$bucket}");
        if (!is_array($quotaConfig)) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        $perActorLimit = max(0, (int) ($quotaConfig['per_actor'] ?? 0));
        $globalLimit = max(0, (int) ($quotaConfig['global'] ?? 0));

        $now = now();
        $dateKey = $now->format('Y-m-d');
        $ttlSeconds = max(60, (int) $now->diffInSeconds($now->copy()->endOfDay()));

        $ip = (string) ($request->ip() ?? 'unknown');
        $userId = $request->user()?->getAuthIdentifier();

        $keysToCheck = [];
        if ($perActorLimit > 0) {
            $keysToCheck["quota:{$dateKey}:{$bucket}:ip:{$ip}"] = $perActorLimit;
            if ($userId !== null) {
                $keysToCheck["quota:{$dateKey}:{$bucket}:user:{$userId}"] = $perActorLimit;
            }
        }
        if ($globalLimit > 0) {
            $keysToCheck["quota:{$dateKey}:{$bucket}:global"] = $globalLimit;
        }

        foreach ($keysToCheck as $cacheKey => $limit) {
            $current = (int) Cache::get($cacheKey, 0);
            if ($current >= $limit) {
                return $this->quotaExceededResponse($request, $limit, $ttlSeconds);
            }
        }

        $remaining = $perActorLimit > 0 ? $perActorLimit : $globalLimit;
        foreach ($keysToCheck as $cacheKey => $limit) {
            Cache::add($cacheKey, 0, $ttlSeconds);
            $newCount = (int) Cache::increment($cacheKey);
            $remaining = min($remaining, max(0, $limit - $newCount));
        }

        /** @var Response $response */
        $response = $next($request);

        $effectiveLimit = $perActorLimit > 0 ? $perActorLimit : $globalLimit;
        if ($effectiveLimit > 0) {
            $response->headers->set('X-Daily-Quota-Limit', (string) $effectiveLimit);
            $response->headers->set('X-Daily-Quota-Remaining', (string) max(0, $remaining));
        }

        return $response;
    }

    private function quotaExceededResponse(Request $request, int $limit, int $retryAfter): Response
    {
        $message = 'Günlük işlem kotası aşıldı. Güvenlik ve maliyet koruması nedeniyle yarına kadar yeni istek kabul edilmiyor.';

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => false,
                'message' => $message,
            ], 429, [
                'Retry-After'             => (string) $retryAfter,
                'X-Daily-Quota-Limit'     => (string) $limit,
                'X-Daily-Quota-Remaining' => '0',
            ]);
        }

        return response($message, 429, [
            'Content-Type'            => 'text/plain; charset=UTF-8',
            'Retry-After'             => (string) $retryAfter,
            'X-Daily-Quota-Limit'     => (string) $limit,
            'X-Daily-Quota-Remaining' => '0',
        ]);
    }
}

