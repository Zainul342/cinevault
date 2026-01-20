<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTimeImmutable;

final class JwtService
{
    private string $secret;
    private int $ttl;
    private string $algo = 'HS256';

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? throw new \RuntimeException('JWT_SECRET not set');
        $this->ttl = (int)($_ENV['JWT_TTL'] ?? 3600);
    }

    public function generate(int $userId, string $role): string
    {
        $issuedAt = new DateTimeImmutable();
        $expire = $issuedAt->modify("+{$this->ttl} seconds");

        $payload = [
            'iat' => $issuedAt->getTimestamp(),
            'iss' => $_ENV['APP_URL'] ?? 'cinevault',
            'nbf' => $issuedAt->getTimestamp(),
            'exp' => $expire->getTimestamp(),
            'sub' => $userId,
            'role' => $role
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function validate(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (\Throwable $e) {
            // Log error if needed: $e->getMessage()
            return null;
        }
    }
}
