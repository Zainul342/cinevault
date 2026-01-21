<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Model\UserInteraction;
use App\Model\Movie;

final class InteractionRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Add user interaction (respects unique constraint)
     */
    public function add(int $userId, int $movieId, string $type): bool
    {
        $sql = "INSERT IGNORE INTO user_interactions (user_id, movie_id, type) 
                VALUES (:userId, :movieId, :type)";
        
        return $this->db->query($sql, [
            'userId' => $userId,
            'movieId' => $movieId,
            'type' => $type,
        ]) > 0;
    }

    /**
     * Remove user interaction
     */
    public function remove(int $userId, int $movieId, string $type): bool
    {
        $sql = "DELETE FROM user_interactions 
                WHERE user_id = :userId AND movie_id = :movieId AND type = :type";
        
        return $this->db->query($sql, [
            'userId' => $userId,
            'movieId' => $movieId,
            'type' => $type,
        ]) > 0;
    }

    /**
     * Check if interaction exists
     */
    public function exists(int $userId, int $movieId, string $type): bool
    {
        $row = $this->db->fetchOne(
            "SELECT 1 FROM user_interactions 
             WHERE user_id = :userId AND movie_id = :movieId AND type = :type",
            ['userId' => $userId, 'movieId' => $movieId, 'type' => $type]
        );
        return $row !== null;
    }

    /**
     * Toggle interaction (add if missing, remove if exists)
     * Returns true if added, false if removed
     */
    public function toggle(int $userId, int $movieId, string $type): bool
    {
        if ($this->exists($userId, $movieId, $type)) {
            $this->remove($userId, $movieId, $type);
            return false;
        }
        $this->add($userId, $movieId, $type);
        return true;
    }

    /**
     * Get user's watchlist with movie details
     * @return array Array of movies with interaction metadata
     */
    public function getWatchlist(int $userId): array
    {
        $sql = "SELECT m.*, ui.created_at as added_at
                FROM user_interactions ui
                JOIN movies m ON ui.movie_id = m.id
                WHERE ui.user_id = :userId AND ui.type = 'watchlist'
                ORDER BY ui.created_at DESC";
        
        return $this->db->fetchAll($sql, ['userId' => $userId]);
    }

    /**
     * Get user's liked movies (IDs only for fast lookup)
     * @return int[]
     */
    public function getUserLikedMovieIds(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT movie_id FROM user_interactions 
             WHERE user_id = :userId AND type = 'like'",
            ['userId' => $userId]
        );
        return array_column($rows, 'movie_id');
    }

    /**
     * Get user's watchlist movie IDs
     * @return int[]
     */
    public function getUserWatchlistIds(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT movie_id FROM user_interactions 
             WHERE user_id = :userId AND type = 'watchlist'",
            ['userId' => $userId]
        );
        return array_column($rows, 'movie_id');
    }

    /**
     * Find users who liked the same movies as given user
     * For collaborative filtering
     * @return array [user_id => count_of_shared_likes]
     */
    public function getSimilarUsers(int $userId, int $limit = 50): array
    {
        $sql = "SELECT ui2.user_id, COUNT(*) as shared_count
                FROM user_interactions ui1
                JOIN user_interactions ui2 ON ui1.movie_id = ui2.movie_id
                WHERE ui1.user_id = :userId 
                  AND ui1.type = 'like'
                  AND ui2.type = 'like'
                  AND ui2.user_id != :userId
                GROUP BY ui2.user_id
                ORDER BY shared_count DESC
                LIMIT " . (int)$limit;
        
        $rows = $this->db->fetchAll($sql, ['userId' => $userId]);
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['user_id']] = (int)$row['shared_count'];
        }
        return $result;
    }

    /**
     * Get movies liked by a set of users (excluding user's own)
     * @param int[] $userIds
     * @param int[] $excludeMovieIds
     * @return array [movie_id => like_count]
     */
    public function getMoviesLikedByUsers(array $userIds, array $excludeMovieIds = []): array
    {
        if (empty($userIds)) return [];

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $params = $userIds;
        
        $excludeClause = '';
        if (!empty($excludeMovieIds)) {
            $excludePlaceholders = implode(',', array_fill(0, count($excludeMovieIds), '?'));
            $excludeClause = " AND movie_id NOT IN ($excludePlaceholders)";
            $params = array_merge($params, $excludeMovieIds);
        }

        $sql = "SELECT movie_id, COUNT(*) as like_count
                FROM user_interactions
                WHERE user_id IN ($placeholders) AND type = 'like' $excludeClause
                GROUP BY movie_id
                ORDER BY like_count DESC";
        
        $rows = $this->db->fetchAllPositional($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['movie_id']] = (int)$row['like_count'];
        }
        return $result;
    }

    /**
     * Count user's interactions by type
     */
    public function countByType(int $userId, string $type): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM user_interactions 
             WHERE user_id = :userId AND type = :type",
            ['userId' => $userId, 'type' => $type]
        );
        return (int)($row['cnt'] ?? 0);
    }
}
