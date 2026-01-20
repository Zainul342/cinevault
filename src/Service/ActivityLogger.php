<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\ActivityLog;
use App\Repository\ActivityLogRepository;

/**
 * Service wrapper untuk logging activity dengan context otomatis.
 * Gunakan ini dari controller untuk tracking user behavior.
 */
final class ActivityLogger
{
    private ActivityLogRepository $repo;

    public function __construct()
    {
        $this->repo = new ActivityLogRepository();
    }

    /**
     * Log activity dengan auto-detect IP dan user agent dari request
     */
    public function log(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): int {
        return $this->repo->log(
            action: $action,
            userId: $userId,
            entityType: $entityType,
            entityId: $entityId,
            metadata: $metadata,
            ipAddress: $this->getClientIp(),
            userAgent: $this->getUserAgent()
        );
    }

    public function logLogin(int $userId): int
    {
        return $this->log(ActivityLog::ACTION_LOGIN, $userId);
    }

    public function logLogout(int $userId): int
    {
        return $this->log(ActivityLog::ACTION_LOGOUT, $userId);
    }

    public function logRegister(int $userId): int
    {
        return $this->log(ActivityLog::ACTION_REGISTER, $userId);
    }

    public function logMovieView(int $movieId, ?int $userId = null): int
    {
        return $this->log(
            ActivityLog::ACTION_MOVIE_VIEW,
            $userId,
            'movie',
            $movieId
        );
    }

    public function logSearch(string $query, ?int $userId = null, int $resultCount = 0): int
    {
        return $this->log(
            ActivityLog::ACTION_MOVIE_SEARCH,
            $userId,
            null,
            null,
            ['query' => $query, 'results' => $resultCount]
        );
    }

    public function logWatchlistAdd(int $movieId, int $userId): int
    {
        return $this->log(
            ActivityLog::ACTION_WATCHLIST_ADD,
            $userId,
            'movie',
            $movieId
        );
    }

    public function logWatchlistRemove(int $movieId, int $userId): int
    {
        return $this->log(
            ActivityLog::ACTION_WATCHLIST_REMOVE,
            $userId,
            'movie',
            $movieId
        );
    }

    private function getClientIp(): ?string
    {
        // Check proxy headers first
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For bisa comma-separated, ambil yang pertama
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    private function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
