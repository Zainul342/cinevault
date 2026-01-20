<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\TmdbService;
use App\Repository\MovieRepository;
use App\Model\Movie;

final class TmdbController
{
    private TmdbService $tmdb;
    private MovieRepository $movieRepo;

    public function __construct()
    {
        $this->tmdb = new TmdbService();
        $this->movieRepo = new MovieRepository();
    }

    public function search(Request $request): Response
    {
        $query = $request->input('query');
        $page = (int) $request->input('page', 1);

        if (!$query) {
            return Response::validation(['query' => 'Search query required']);
        }

        try {
            $data = $this->tmdb->searchMovie((string)$query, $page);
            return Response::success($data);
        } catch (\Throwable $e) {
            return Response::error('TMDB_ERROR', $e->getMessage(), 502);
        }
    }

    public function trending(Request $request): Response
    {
        $window = $request->input('window', 'week');
        
        try {
            $data = $this->tmdb->getTrending((string)$window);
            return Response::success($data);
        } catch (\Throwable $e) {
            return Response::error('TMDB_ERROR', $e->getMessage(), 502);
        }
    }

    public function sync(Request $request): Response
    {
        $tmdbId = (int) $request->input('tmdb_id');
        
        if (!$tmdbId) {
            return Response::validation(['tmdb_id' => 'TMDB ID required']);
        }

        // Check if already exists
        $existing = $this->movieRepo->findByTmdbId($tmdbId);
        if ($existing) {
            return Response::success(['message' => 'Movie already synced', 'id' => $existing->id]);
        }

        try {
            $details = $this->tmdb->getMovieDetails($tmdbId);
            if (!$details) {
                return Response::notFound('Movie not found on TMDB');
            }

            // Map TMDB response to Movie model
            $movie = new Movie(
                id: null,
                tmdbId: $details['id'],
                title: $details['title'],
                slug: $this->slugify($details['title'] . '-' . $details['release_date']),
                overview: $details['overview'] ?? null,
                posterPath: $details['poster_path'] ?? null,
                backdropPath: $details['backdrop_path'] ?? null,
                releaseDate: $details['release_date'] ?? null,
                voteAverage: (float)($details['vote_average'] ?? 0)
            );

            $id = $this->movieRepo->save($movie);

            return Response::success(['message' => 'Movie synced', 'id' => $id], 201);

        } catch (\Throwable $e) {
            return Response::error('SYNC_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * Get movie details from TMDB (proxy endpoint)
     */
    public function details(Request $request): Response
    {
        $tmdbId = (int) $request->param('id');
        
        if (!$tmdbId) {
            return Response::validation(['id' => 'TMDB ID required']);
        }

        try {
            $details = $this->tmdb->getMovieDetails($tmdbId);
            if (!$details) {
                return Response::notFound('Movie not found on TMDB');
            }
            return Response::success(['data' => $details]);
        } catch (\Throwable $e) {
            return Response::error('TMDB_ERROR', $e->getMessage(), 502);
        }
    }

    /**
     * Sync multiple movies from TMDB at once
     */
    public function syncBatch(Request $request): Response
    {
        $tmdbIds = $request->input('tmdb_ids');
        
        if (!is_array($tmdbIds) || empty($tmdbIds)) {
            return Response::validation(['tmdb_ids' => 'Array of TMDB IDs required']);
        }

        // Limit batch size for performance
        $tmdbIds = array_slice($tmdbIds, 0, 20);
        
        $synced = [];
        $skipped = [];
        $failed = [];

        foreach ($tmdbIds as $tmdbId) {
            $tmdbId = (int) $tmdbId;
            if ($tmdbId <= 0) continue;

            // Already exists?
            $existing = $this->movieRepo->findByTmdbId($tmdbId);
            if ($existing) {
                $skipped[] = ['tmdb_id' => $tmdbId, 'id' => $existing->id];
                continue;
            }

            try {
                $details = $this->tmdb->getMovieDetails($tmdbId);
                if (!$details) {
                    $failed[] = ['tmdb_id' => $tmdbId, 'reason' => 'Not found on TMDB'];
                    continue;
                }

                $movie = new Movie(
                    id: null,
                    tmdbId: $details['id'],
                    title: $details['title'],
                    slug: $this->slugify($details['title'] . '-' . ($details['release_date'] ?? $tmdbId)),
                    overview: $details['overview'] ?? null,
                    posterPath: $details['poster_path'] ?? null,
                    backdropPath: $details['backdrop_path'] ?? null,
                    releaseDate: $details['release_date'] ?? null,
                    voteAverage: (float)($details['vote_average'] ?? 0)
                );

                $id = $this->movieRepo->save($movie);
                $synced[] = ['tmdb_id' => $tmdbId, 'id' => $id, 'title' => $details['title']];
            } catch (\Throwable $e) {
                $failed[] = ['tmdb_id' => $tmdbId, 'reason' => $e->getMessage()];
            }
        }

        return Response::success([
            'synced' => $synced,
            'skipped' => $skipped,
            'failed' => $failed,
            'summary' => [
                'synced_count' => count($synced),
                'skipped_count' => count($skipped),
                'failed_count' => count($failed),
            ]
        ]);
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        return preg_replace('/[^a-z0-9-]+/', '-', $text);
    }
}
