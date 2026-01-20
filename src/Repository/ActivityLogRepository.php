<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Model\ActivityLog;

final class ActivityLogRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Log an activity
     */
    public function log(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO activity_logs 
                (user_id, action, entity_type, entity_id, metadata, ip_address, user_agent)
            VALUES 
                (:user_id, :action, :entity_type, :entity_id, :metadata, :ip_address, :user_agent)
        ');

        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? substr($userAgent, 0, 512) : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get logs by user with pagination
     * @return ActivityLog[]
     */
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM activity_logs 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn(array $row) => ActivityLog::fromArray($row),
            $stmt->fetchAll(\PDO::FETCH_ASSOC)
        );
    }

    /**
     * Get action count untuk analytics (e.g., jumlah login per hari)
     * @return array<string, int>
     */
    public function countByAction(string $action, string $period = 'day'): array
    {
        $dateFormat = match ($period) {
            'hour' => '%Y-%m-%d %H:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(created_at, :format) as period,
                COUNT(*) as total
            FROM activity_logs 
            WHERE action = :action
            GROUP BY period
            ORDER BY period DESC
            LIMIT 30
        ");
        $stmt->execute(['format' => $dateFormat, 'action' => $action]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[$row['period']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Get most viewed movies
     * @return array<int, int> [movie_id => view_count]
     */
    public function getTopViewedMovies(int $limit = 10, int $daysBack = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT entity_id, COUNT(*) as views
            FROM activity_logs
            WHERE action = :action 
                AND entity_type = 'movie'
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY entity_id
            ORDER BY views DESC
            LIMIT :limit
        ");
        $stmt->bindValue('action', ActivityLog::ACTION_MOVIE_VIEW, \PDO::PARAM_STR);
        $stmt->bindValue('days', $daysBack, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[(int) $row['entity_id']] = (int) $row['views'];
        }

        return $result;
    }

    /**
     * Get popular search terms
     * @return array<string, int>
     */
    public function getPopularSearches(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.query')) as query,
                COUNT(*) as total
            FROM activity_logs
            WHERE action = :action
                AND metadata IS NOT NULL
            GROUP BY query
            ORDER BY total DESC
            LIMIT :limit
        ");
        $stmt->bindValue('action', ActivityLog::ACTION_MOVIE_SEARCH, \PDO::PARAM_STR);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if ($row['query']) {
                $result[$row['query']] = (int) $row['total'];
            }
        }

        return $result;
    }

    /**
     * Get daily active users count
     */
    public function getDailyActiveUsers(int $daysBack = 7): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(DISTINCT user_id) as users
            FROM activity_logs
            WHERE user_id IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY date
            ORDER BY date DESC
        ");
        $stmt->bindValue('days', $daysBack, \PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[$row['date']] = (int) $row['users'];
        }

        return $result;
    }

    /**
     * Cleanup old logs (retention policy)
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $stmt = $this->db->prepare('
            DELETE FROM activity_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ');
        $stmt->execute(['days' => $daysToKeep]);

        return $stmt->rowCount();
    }
}
