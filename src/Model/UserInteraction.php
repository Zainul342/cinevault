<?php

declare(strict_types=1);

namespace App\Model;

final class UserInteraction
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public int $movieId,
        public string $type, // 'watchlist', 'like', 'history'
        public ?string $createdAt = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            userId: (int) $data['user_id'],
            movieId: (int) $data['movie_id'],
            type: $data['type'],
            createdAt: $data['created_at'] ?? null
        );
    }
}
