<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;
use Closure;

final class Router
{
    /** @var array<string, array<array{pattern: string, handler: callable, middleware: array<class-string<MiddlewareInterface>>}>> */
    private array $routes = [];

    /** @var array<class-string<MiddlewareInterface>> Global middleware */
    private array $globalMiddleware = [];

    /**
     * @param array<class-string<MiddlewareInterface>> $middleware
     */
    public function get(string $path, callable $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * @param array<class-string<MiddlewareInterface>> $middleware
     */
    public function post(string $path, callable $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * @param array<class-string<MiddlewareInterface>> $middleware
     */
    public function put(string $path, callable $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * @param array<class-string<MiddlewareInterface>> $middleware
     */
    public function patch(string $path, callable $handler, array $middleware = []): self
    {
        return $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * @param array<class-string<MiddlewareInterface>> $middleware
     */
    public function delete(string $path, callable $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * @param class-string<MiddlewareInterface> $middleware
     */
    public function use(string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * @param array<class-string<MiddlewareInterface>> $middleware
     */
    private function addRoute(string $method, string $path, callable $handler, array $middleware): self
    {
        // Convert {param} to regex groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
        
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method;
        $path = $request->path;
        
        // Handle OPTIONS preflight
        if ($method === 'OPTIONS') {
            return Response::success();
        }
        
        $routes = $this->routes[$method] ?? [];
        
        foreach ($routes as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named params
                $params = array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
                $request->setParams($params);
                
                // Build middleware pipeline
                $allMiddleware = array_merge($this->globalMiddleware, $route['middleware']);
                
                return $this->runMiddleware($request, $allMiddleware, $route['handler']);
            }
        }
        
        return Response::notFound("Route not found: {$method} {$path}");
    }

    /**
     * @param array<class-string<MiddlewareInterface>> $middleware
     */
    private function runMiddleware(Request $request, array $middleware, callable $handler): Response
    {
        if (empty($middleware)) {
            return $this->callHandler($request, $handler);
        }
        
        $middlewareClass = array_shift($middleware);
        $instance = new $middlewareClass();
        
        $next = fn(Request $req) => $this->runMiddleware($req, $middleware, $handler);
        
        return $instance->handle($request, $next);
    }

    private function callHandler(Request $request, callable $handler): Response
    {
        $result = $handler($request);
        
        if ($result instanceof Response) {
            return $result;
        }
        
        // Array atau object → JSON response
        if (is_array($result) || is_object($result)) {
            return Response::success($result);
        }
        
        // String → plain text
        return (new Response())
            ->header('Content-Type', 'text/plain')
            ->status(200);
    }
}
