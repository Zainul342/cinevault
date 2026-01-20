<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Service\JwtService;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    private JwtService $jwt;

    public function __construct()
    {
        $this->jwt = new JwtService();
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if ($token === null) {
            return Response::unauthorized('Missing authentication token');
        }
        
        $payload = $this->jwt->validate($token);
        
        if ($payload === null) {
            return Response::unauthorized('Invalid or expired token');
        }
        
        // Set attributes from token payload
        $request->setAttribute('user_id', $payload->sub);
        $request->setAttribute('user_role', $payload->role);
        
        return $next($request);
    }
}
