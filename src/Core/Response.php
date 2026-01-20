<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_UNPROCESSABLE = 422;
    public const HTTP_TOO_MANY = 429;
    public const HTTP_INTERNAL_ERROR = 500;

    private int $statusCode = 200;
    
    /** @var array<string, string> */
    private array $headers = [];
    
    private mixed $body = null;

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @param array<string, mixed>|object $data
     */
    public function json(array|object $data, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'application/json';
        $this->body = $data;
        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data = [], int $status = 200): self
    {
        return (new self())->json($data, $status);
    }

    public static function error(
        string $code,
        string $message,
        int $status = 400,
        ?array $details = null
    ): self {
        $payload = [
            'error' => $code,
            'message' => $message,
        ];
        if ($details !== null) {
            $payload['details'] = $details;
        }
        return (new self())->json($payload, $status);
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return self::error('NOT_FOUND', $message, self::HTTP_NOT_FOUND);
    }

    public static function unauthorized(string $message = 'Authentication required'): self
    {
        return self::error('UNAUTHORIZED', $message, self::HTTP_UNAUTHORIZED);
    }

    public static function forbidden(string $message = 'Access denied'): self
    {
        return self::error('FORBIDDEN', $message, self::HTTP_FORBIDDEN);
    }

    public static function validation(array $errors): self
    {
        return self::error(
            'VALIDATION_ERROR',
            'Input validation failed',
            self::HTTP_UNPROCESSABLE,
            $errors
        );
    }

    public static function tooMany(int $retryAfter = 60): self
    {
        return (new self())
            ->header('Retry-After', (string) $retryAfter)
            ->json([
                'error' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests',
                'retry_after' => $retryAfter,
            ], self::HTTP_TOO_MANY);
    }

    public function send(): never
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        if ($this->body !== null) {
            if (($this->headers['Content-Type'] ?? '') === 'application/json') {
                echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo $this->body;
            }
        }
        
        exit;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }
}
