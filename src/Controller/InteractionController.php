<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Repository\InteractionRepository;
use App\Repository\MovieRepository;
use App\Service\RecommendationService;
use App\Service\ActivityLogger;

final class InteractionController
{
    private InteractionRepository $repo;
    private MovieRepository $movies;
    private ActivityLogger $logger;

    public function __construct()
    {
        $this->repo = new InteractionRepository();
        $this->movies = new MovieRepository();
        $this->logger = new ActivityLogger();
    }

    /**
     * GET /api/watchlist
     */
    public function listWatchlist(Request $req): array
    {
        $userId = $req->getAttribute('user_id');
        $items = $this->repo->getWatchlist($userId);
        
        return [
            'watchlist' => $items,
            'count' => count($items),
        ];
    }

    /**
     * POST /api/watchlist/{movieId}
     */
    public function addToWatchlist(Request $req): array
    {
        $userId = $req->getAttribute('user_id');
        $movieId = (int) $req->param('movieId');

        $movie = $this->movies->findById($movieId);
        if (!$movie) {
            return Response::json(['error' => 'MOVIE_NOT_FOUND'], 404);
        }

        $added = $this->repo->add($userId, $movieId, 'watchlist');
        
        $this->logger->log('watchlist_add', $userId, 'movie', $movieId);

        return [
            'success' => true,
            'added' => $added,
            'message' => $added ? 'Added to watchlist' : 'Already in watchlist',
        ];
    }

    /**
     * DELETE /api/watchlist/{movieId}
     */
    public function removeFromWatchlist(Request $req): array
    {
        $userId = $req->getAttribute('user_id');
        $movieId = (int) $req->param('movieId');

        $removed = $this->repo->remove($userId, $movieId, 'watchlist');
        
        if ($removed) {
            $this->logger->log('watchlist_remove', $userId, 'movie', $movieId);
        }

        return [
            'success' => true,
            'removed' => $removed,
            'message' => $removed ? 'Removed from watchlist' : 'Not in watchlist',
        ];
    }

    /**
     * POST /api/movies/{id}/like - Toggle like
     */
    public function toggleLike(Request $req): array
    {
        $userId = $req->getAttribute('user_id');
        $movieId = (int) $req->param('id');

        $movie = $this->movies->findById($movieId);
        if (!$movie) {
            return Response::json(['error' => 'MOVIE_NOT_FOUND'], 404);
        }

        $isLiked = $this->repo->toggle($userId, $movieId, 'like');
        
        $this->logger->log($isLiked ? 'movie_like' : 'movie_unlike', $userId, 'movie', $movieId);

        return [
            'success' => true,
            'liked' => $isLiked,
            'message' => $isLiked ? 'Movie liked' : 'Like removed',
        ];
    }

    /**
     * POST /api/movies/{id}/history - Track view
     */
    public function trackHistory(Request $req): array
    {
        $userId = $req->getAttribute('user_id');
        $movieId = (int) $req->param('id');

        // History can have duplicates over time, just add
        $this->repo->add($userId, $movieId, 'history');
        $this->logger->log('movie_view', $userId, 'movie', $movieId);

        return ['success' => true, 'tracked' => true];
    }

    /**
     * GET /api/recommendations
     */
    public function getRecommendations(Request $req): array
    {
        $userId = $req->getAttribute('user_id');
        $limit = min((int)($req->input('limit', 12)), 24);

        $service = new RecommendationService();
        $recommendations = $service->getRecommendations($userId, $limit);
        $stats = $service->getStats($userId);

        return [
            'recommendations' => $recommendations,
            'stats' => $stats,
            'count' => count($recommendations),
        ];
    }

    /**
     * GET /api/interactions/status/{movieId}
     * Check if user has liked/watchlisted a specific movie
     */
    public function getStatus(Request $req): array
    {
        $userId = $req->getAttribute('user_id');
        $movieId = (int) $req->param('movieId');

        return [
            'movie_id' => $movieId,
            'liked' => $this->repo->exists($userId, $movieId, 'like'),
            'in_watchlist' => $this->repo->exists($userId, $movieId, 'watchlist'),
        ];
    }
}
