<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class CorsMiddleware implements MiddlewareInterface
{
    private const ALLOWED_ORIGINS = ['*']; // Atau specify domain
    private const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    private const ALLOWED_HEADERS = ['Content-Type', 'Authorization', 'X-Requested-With'];
    private const MAX_AGE = 86400; // 24 jam cache preflight

    public function handle(Request $request, Closure $next): Response
    {
        // Preflight request
        if ($request->method === 'OPTIONS') {
            return $this->preflightResponse();
        }
        
        $response = $next($request);
        
        return $this->addCorsHeaders($response, $request);
    }

    private function preflightResponse(): Response
    {
        return (new Response())
            ->status(204)
            ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin())
            ->header('Access-Control-Allow-Methods', implode(', ', self::ALLOWED_METHODS))
            ->header('Access-Control-Allow-Headers', implode(', ', self::ALLOWED_HEADERS))
            ->header('Access-Control-Max-Age', (string) self::MAX_AGE);
    }

    private function addCorsHeaders(Response $response, Request $request): Response
    {
        return $response
            ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin())
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    private function getAllowedOrigin(): string
    {
        // Bisa dibuat dynamic berdasarkan env
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        if (in_array('*', self::ALLOWED_ORIGINS, true)) {
            return '*';
        }
        
        return in_array($origin, self::ALLOWED_ORIGINS, true) ? $origin : self::ALLOWED_ORIGINS[0];
    }
}
