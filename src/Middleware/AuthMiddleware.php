<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Auth middleware - stub implementation
 * Full JWT validation akan diimplementasi di Phase 2
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if ($token === null) {
            return Response::unauthorized('Missing authentication token');
        }
        
        // TODO Phase 2: Validate JWT token dengan JwtService
        // Untuk sekarang, cek apakah token ada saja
        
        // Placeholder - set user data di request attributes
        // Nanti akan diganti dengan decoded JWT payload
        $request->setAttribute('user_id', null);
        $request->setAttribute('user_role', 'guest');
        
        return $next($request);
    }
}
