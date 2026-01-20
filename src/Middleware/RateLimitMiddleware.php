<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storagePath;

    public function __construct(
        ?int $maxRequests = null,
        ?int $windowSeconds = null
    ) {
        $this->maxRequests = $maxRequests ?? (int) ($_ENV['RATE_LIMIT_MAX'] ?? 100);
        $this->windowSeconds = $windowSeconds ?? (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
        $this->storagePath = sys_get_temp_dir() . '/cinevault_ratelimit';
        
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        $identifier = $this->getIdentifier($request);
        $data = $this->getData($identifier);
        
        $now = time();
        $windowStart = $now - $this->windowSeconds;
        
        // Cleanup old entries
        $data = array_filter($data, fn($ts) => $ts > $windowStart);
        
        if (count($data) >= $this->maxRequests) {
            $oldestInWindow = min($data);
            $retryAfter = $this->windowSeconds - ($now - $oldestInWindow);
            
            return Response::tooMany(max(1, $retryAfter));
        }
        
        // Record request
        $data[] = $now;
        $this->saveData($identifier, $data);
        
        $response = $next($request);
        
        // Add rate limit headers
        $remaining = $this->maxRequests - count($data);
        
        return $response
            ->header('X-RateLimit-Limit', (string) $this->maxRequests)
            ->header('X-RateLimit-Remaining', (string) max(0, $remaining))
            ->header('X-RateLimit-Reset', (string) ($now + $this->windowSeconds));
    }

    private function getIdentifier(Request $request): string
    {
        // Bisa di-extend untuk authenticated user
        $userId = $request->getAttribute('user_id');
        if ($userId !== null) {
            return 'user_' . $userId;
        }
        
        return 'ip_' . md5($request->ip());
    }

    /**
     * @return array<int>
     */
    private function getData(string $identifier): array
    {
        $file = $this->storagePath . '/' . $identifier . '.json';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }
        
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<int> $data
     */
    private function saveData(string $identifier, array $data): void
    {
        $file = $this->storagePath . '/' . $identifier . '.json';
        file_put_contents($file, json_encode(array_values($data)));
    }
}
