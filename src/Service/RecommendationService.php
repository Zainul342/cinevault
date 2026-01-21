<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\InteractionRepository;
use App\Repository\MovieRepository;

/**
 * Recommendation engine using item-based collaborative filtering
 * 
 * Algorithm:
 * 1. Find movies the user has liked
 * 2. Find other users who liked the same movies (similar users)
 * 3. Get movies those similar users also liked (excluding user's own)
 * 4. Rank by co-occurrence frequency
 * 5. Fall back to popular movies if insufficient data
 */
final class RecommendationService
{
    private InteractionRepository $interactions;
    private MovieRepository $movies;

    public function __construct()
    {
        $this->interactions = new InteractionRepository();
        $this->movies = new MovieRepository();
    }

    /**
     * Get personalized movie recommendations for a user
     * @return array Array of movie data with recommendation scores
     */
    public function getRecommendations(int $userId, int $limit = 12): array
    {
        $userLikedIds = $this->interactions->getUserLikedMovieIds($userId);
        $userWatchlistIds = $this->interactions->getUserWatchlistIds($userId);
        
        // Movies to exclude from recommendations
        $excludeIds = array_unique(array_merge($userLikedIds, $userWatchlistIds));

        // Need at least a few likes to generate collaborative recommendations
        if (count($userLikedIds) < 2) {
            return $this->getFallbackRecommendations($excludeIds, $limit);
        }

        // Step 1: Find similar users based on shared likes
        $similarUsers = $this->interactions->getSimilarUsers($userId, 30);
        
        if (empty($similarUsers)) {
            return $this->getFallbackRecommendations($excludeIds, $limit);
        }

        // Step 2: Get movies liked by similar users
        $candidateMovies = $this->interactions->getMoviesLikedByUsers(
            array_keys($similarUsers),
            $excludeIds
        );

        if (empty($candidateMovies)) {
            return $this->getFallbackRecommendations($excludeIds, $limit);
        }

        // Step 3: Score movies by weighted co-occurrence
        // Weight by similarity (shared likes count) of recommending user
        $scored = [];
        foreach ($candidateMovies as $movieId => $likeCount) {
            // Simple scoring: frequency of likes among similar users
            // Could be enhanced with similarity weighting
            $scored[$movieId] = $likeCount;
        }

        // Sort by score descending
        arsort($scored);
        $topMovieIds = array_slice(array_keys($scored), 0, $limit);

        // Fetch full movie data
        return $this->fetchMoviesWithScores($topMovieIds, $scored);
    }

    /**
     * Fallback: return popular movies user hasn't seen
     */
    private function getFallbackRecommendations(array $excludeIds, int $limit): array
    {
        $popular = $this->movies->getPopular($limit + count($excludeIds));
        
        $filtered = array_filter($popular, function($movie) use ($excludeIds) {
            return !in_array($movie->id, $excludeIds);
        });

        return array_map(function($movie) {
            $data = $movie->toArray();
            $data['recommendation_score'] = 0;
            $data['recommendation_reason'] = 'popular';
            return $data;
        }, array_slice($filtered, 0, $limit));
    }

    /**
     * Fetch movies by IDs and attach recommendation scores
     */
    private function fetchMoviesWithScores(array $movieIds, array $scores): array
    {
        $result = [];
        foreach ($movieIds as $movieId) {
            $movie = $this->movies->findById($movieId);
            if ($movie) {
                $data = $movie->toArray();
                $data['recommendation_score'] = $scores[$movieId] ?? 0;
                $data['recommendation_reason'] = 'collaborative';
                $result[] = $data;
            }
        }
        return $result;
    }

    /**
     * Get quick stats for the recommendation system
     */
    public function getStats(int $userId): array
    {
        return [
            'liked_count' => $this->interactions->countByType($userId, 'like'),
            'watchlist_count' => $this->interactions->countByType($userId, 'watchlist'),
            'history_count' => $this->interactions->countByType($userId, 'history'),
        ];
    }
}
