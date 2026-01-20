<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Repository\MovieRepository;

final class MovieController
{
    private MovieRepository $movieRepo;

    public function __construct()
    {
        $this->movieRepo = new MovieRepository();
    }

    public function index(Request $request): Response
    {
        $limit = (int) $request->input('limit', 20);
        $sort = $request->input('sort', 'latest');
        
        $movies = match($sort) {
            'popular' => $this->movieRepo->getPopular($limit),
            default => $this->movieRepo->getLatest($limit),
        };
        
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
        
        return Response::success(['data' => $movie->toArray()]);
    }
}
