<?php

declare(strict_types=1);

namespace App\Model;

final class Movie
{
    public function __construct(
        public ?int $id,
        public int $tmdbId,
        public string $title,
        public string $slug,
        public ?string $overview,
        public ?string $posterPath,
        public ?string $backdropPath,
        public ?string $releaseDate,
        public float $voteAverage,
        public ?string $createdAt = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            tmdbId: (int) $data['tmdb_id'],
            title: $data['title'],
            slug: $data['slug'],
            overview: $data['overview'] ?? null,
            posterPath: $data['poster_path'] ?? null,
            backdropPath: $data['backdrop_path'] ?? null,
            releaseDate: $data['release_date'] ?? null,
            voteAverage: (float) ($data['vote_average'] ?? 0.0),
            createdAt: $data['created_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdbId,
            'title' => $this->title,
            'slug' => $this->slug,
            'overview' => $this->overview,
            'poster_path' => $this->posterPath,
            'backdrop_path' => $this->backdropPath,
            'release_date' => $this->releaseDate,
            'vote_average' => $this->voteAverage,
            'created_at' => $this->createdAt,
        ];
    }
}
