<?php

declare(strict_types=1);

namespace App\Model;

/**
 * ActivityLog untuk tracking user behavior dan analytics.
 * 
 * Action types:
 * - auth.login, auth.logout, auth.register
 * - movie.view, movie.search
 * - watchlist.add, watchlist.remove
 * - page.visit
 */
final class ActivityLog
{
    public const ACTION_LOGIN = 'auth.login';
    public const ACTION_LOGOUT = 'auth.logout';
    public const ACTION_REGISTER = 'auth.register';
    public const ACTION_MOVIE_VIEW = 'movie.view';
    public const ACTION_MOVIE_SEARCH = 'movie.search';
    public const ACTION_WATCHLIST_ADD = 'watchlist.add';
    public const ACTION_WATCHLIST_REMOVE = 'watchlist.remove';
    public const ACTION_PAGE_VISIT = 'page.visit';

    public function __construct(
        public ?int $id,
        public ?int $userId,
        public string $action,
        public ?string $entityType = null,
        public ?int $entityId = null,
        public ?array $metadata = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $createdAt = null
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $metadata = $data['metadata'] ?? null;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            action: $data['action'],
            entityType: $data['entity_type'] ?? null,
            entityId: isset($data['entity_id']) ? (int) $data['entity_id'] : null,
            metadata: $metadata,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            createdAt: $data['created_at'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'action' => $this->action,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'metadata' => $this->metadata,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt,
        ];
    }
}
