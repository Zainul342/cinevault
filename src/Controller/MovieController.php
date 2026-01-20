<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Model\Movie;
use App\Repository\MovieRepository;
use App\Service\ActivityLogger;

final class MovieController
{
    private MovieRepository $movieRepo;
    private ActivityLogger $logger;

    public function __construct()
    {
        $this->movieRepo = new MovieRepository();
        $this->logger = new ActivityLogger();
    }

    public function index(Request $request): Response
    {
        $limit = (int) $request->input('limit', 20);
        $sort = $request->input('sort', 'latest');
        $query = $request->input('q');
        
        if ($query) {
            $movies = $this->movieRepo->search((string)$query, $limit);
        } else {
            $movies = match($sort) {
                'popular' => $this->movieRepo->getPopular($limit),
                default => $this->movieRepo->getLatest($limit),
            };
        }
        
        return Response::success([
            'data' => array_map(fn($m) => $m->toArray(), $movies)
        ]);
    }

    public function show(Request $request): Response
    {
        $id = $request->param('id');
        
        $movie = is_numeric($id) 
            ? $this->movieRepo->findById((int)$id)
            : $this->movieRepo->findBySlug((string)$id);
            
        if (!$movie) {
            return Response::notFound('Movie not found');
        }
        
        // Track movie view
        $userId = $request->getAttribute('user_id');
        $this->logger->logMovieView($movie->id ?? 0, $userId ? (int)$userId : null);
        
        return Response::success(['data' => $movie->toArray()]);
    }

    /**
     * Create movie manually (admin only)
     */
    public function store(Request $request): Response
    {
        $title = $request->input('title');
        $tmdbId = (int) $request->input('tmdb_id', 0);
        
        if (!$title) {
            return Response::validation(['title' => 'Title is required']);
        }

        $movie = new Movie(
            id: null,
            tmdbId: $tmdbId ?: random_int(900000, 999999), // Fake ID for manual entries
            title: $title,
            slug: $this->slugify($title),
            overview: $request->input('overview'),
            posterPath: $request->input('poster_path'),
            backdropPath: $request->input('backdrop_path'),
            releaseDate: $request->input('release_date'),
            voteAverage: (float) $request->input('vote_average', 0)
        );

        $id = $this->movieRepo->save($movie);
        $movie = $this->movieRepo->findById($id);

        return Response::success([
            'message' => 'Movie created',
            'data' => $movie?->toArray()
        ], 201);
    }

    /**
     * Update movie (admin only)
     */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $movie = $this->movieRepo->findById($id);
        
        if (!$movie) {
            return Response::notFound('Movie not found');
        }

        // Update fields if provided
        $updated = new Movie(
            id: $movie->id,
            tmdbId: $movie->tmdbId,
            title: $request->input('title') ?? $movie->title,
            slug: $request->input('title') ? $this->slugify($request->input('title')) : $movie->slug,
            overview: $request->input('overview') ?? $movie->overview,
            posterPath: $request->input('poster_path') ?? $movie->posterPath,
            backdropPath: $request->input('backdrop_path') ?? $movie->backdropPath,
            releaseDate: $request->input('release_date') ?? $movie->releaseDate,
            voteAverage: $request->has('vote_average') 
                ? (float) $request->input('vote_average') 
                : $movie->voteAverage
        );

        $this->movieRepo->save($updated);

        return Response::success([
            'message' => 'Movie updated',
            'data' => $this->movieRepo->findById($id)?->toArray()
        ]);
    }

    /**
     * Delete movie (admin only)
     */
    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $movie = $this->movieRepo->findById($id);
        
        if (!$movie) {
            return Response::notFound('Movie not found');
        }

        $this->movieRepo->delete($id);

        return Response::success(['message' => 'Movie deleted']);
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        return preg_replace('/[^a-z0-9-]+/', '-', $text) ?? $text;
    }
}
