<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Model\Movie;

final class MovieRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?Movie
    {
        $data = $this->db->fetchOne("SELECT * FROM movies WHERE id = :id", ['id' => $id]);
        return $data ? Movie::fromArray($data) : null;
    }

    public function findBySlug(string $slug): ?Movie
    {
        $data = $this->db->fetchOne("SELECT * FROM movies WHERE slug = :slug", ['slug' => $slug]);
        return $data ? Movie::fromArray($data) : null;
    }

    public function findByTmdbId(int $tmdbId): ?Movie
    {
        $data = $this->db->fetchOne("SELECT * FROM movies WHERE tmdb_id = :id", ['id' => $tmdbId]);
        return $data ? Movie::fromArray($data) : null;
    }

    /**
     * @return Movie[]
     */
    public function getLatest(int $limit = 10): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM movies ORDER BY release_date DESC LIMIT " . (int)$limit);
        return array_map(fn($row) => Movie::fromArray($row), $rows);
    }

    /**
     * @return Movie[]
     */
    public function getPopular(int $limit = 10): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM movies ORDER BY vote_average DESC LIMIT " . (int)$limit);
        return array_map(fn($row) => Movie::fromArray($row), $rows);
    }

    public function save(Movie $movie): int
    {
        if ($movie->id) {
            $this->update($movie);
            return $movie->id;
        }
        return $this->create($movie);
    }

    private function create(Movie $movie): int
    {
        $this->db->query(
            "INSERT INTO movies (tmdb_id, title, slug, overview, poster_path, backdrop_path, release_date, vote_average) 
             VALUES (:tmdbId, :title, :slug, :overview, :posterPath, :backdropPath, :releaseDate, :voteAverage)",
            [
                'tmdbId' => $movie->tmdbId,
                'title' => $movie->title,
                'slug' => $movie->slug,
                'overview' => $movie->overview,
                'posterPath' => $movie->posterPath,
                'backdropPath' => $movie->backdropPath,
                'releaseDate' => $movie->releaseDate,
                'voteAverage' => $movie->voteAverage,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    private function update(Movie $movie): void
    {
        $this->db->query(
            "UPDATE movies SET 
                tmdb_id = :tmdbId, title = :title, slug = :slug, overview = :overview, 
                poster_path = :posterPath, backdrop_path = :backdropPath, 
                release_date = :releaseDate, vote_average = :voteAverage 
             WHERE id = :id",
            [
                'id' => $movie->id,
                'tmdbId' => $movie->tmdbId,
                'title' => $movie->title,
                'slug' => $movie->slug,
                'overview' => $movie->overview,
                'posterPath' => $movie->posterPath,
                'backdropPath' => $movie->backdropPath,
                'releaseDate' => $movie->releaseDate,
                'voteAverage' => $movie->voteAverage,
            ]
        );
    }
}
