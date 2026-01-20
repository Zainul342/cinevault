<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    private static ?self $instance = null;
    
    public readonly Router $router;
    
    /** @var array<string, mixed> Service container */
    private array $services = [];

    private function __construct()
    {
        $this->router = new Router();
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Register a service
     * @param callable(): mixed $factory
     */
    public function bind(string $name, callable $factory): void
    {
        $this->services[$name] = $factory;
    }

    /**
     * Get a service (lazy instantiation)
     */
    public function get(string $name): mixed
    {
        if (!isset($this->services[$name])) {
            throw new \RuntimeException("Service not found: {$name}");
        }
        
        $service = $this->services[$name];
        
        // Lazy init - replace factory with instance
        if (is_callable($service)) {
            $this->services[$name] = $service();
        }
        
        return $this->services[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        $request = new Request();
        $response = $this->router->dispatch($request);
        $response->send();
    }

    /**
     * Get config value
     * @param array<string, mixed>|null $default
     * @return array<string, mixed>
     */
    public function config(string $file): array
    {
        static $cache = [];
        
        if (!isset($cache[$file])) {
            $path = __DIR__ . "/../../config/{$file}.php";
            if (!file_exists($path)) {
                throw new \RuntimeException("Config file not found: {$file}");
            }
            $cache[$file] = require $path;
        }
        
        return $cache[$file];
    }
}
