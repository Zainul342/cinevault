<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class RoleMiddleware implements MiddlewareInterface
{
    /** @var array<string> */
    private array $allowedRoles;

    /**
     * @param string|array<string> $roles
     */
    public function __construct(string|array $roles = [])
    {
        $this->allowedRoles = is_array($roles) ? $roles : [$roles];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $userRole = $request->getAttribute('user_role');
        
        if ($userRole === null) {
            return Response::unauthorized('Authentication required');
        }
        
        // Empty allowed = semua role boleh asal authenticated
        if (empty($this->allowedRoles)) {
            return $next($request);
        }
        
        if (!in_array($userRole, $this->allowedRoles, true)) {
            return Response::forbidden(
                "Role '{$userRole}' is not authorized for this resource"
            );
        }
        
        return $next($request);
    }

    /**
     * Factory untuk admin-only routes
     */
    public static function admin(): self
    {
        return new self(['admin']);
    }

    /**
     * Factory untuk admin atau moderator
     */
    public static function moderator(): self
    {
        return new self(['admin', 'moderator']);
    }

    /**
     * Factory untuk semua authenticated user
     */
    public static function authenticated(): self
    {
        return new self([]);
    }
}
