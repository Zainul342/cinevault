<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class TmdbService
{
    private Client $client;
    private string $apiKey;
    
    public function __construct()
    {
        $this->apiKey = $_ENV['TMDB_API_KEY'] ?? getenv('TMDB_API_KEY') ?: throw new \RuntimeException('TMDB_API_KEY not set');
        $baseUrl = $_ENV['TMDB_BASE_URL'] ?? getenv('TMDB_BASE_URL') ?: 'https://api.themoviedb.org/3';
        
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout'  => 5.0,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function searchMovie(string $query, int $page = 1): array
    {
        return $this->request('GET', '/search/movie', [
            'query' => $query,
            'page' => $page,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrending(string $timeWindow = 'week'): array
    {
        return $this->request('GET', "/trending/movie/{$timeWindow}");
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMovieDetails(int $tmdbId): ?array
    {
        try {
            return $this->request('GET', "/movie/{$tmdbId}");
        } catch (GuzzleException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, array $query = []): array
    {
        $query['api_key'] = $this->apiKey;
        
        try {
            $response = $this->client->request($method, $uri, [
                'query' => $query,
                'verify' => false, // For easier dev environment setup (CAUTION in prod)
            ]);
            
            $contents = $response->getBody()->getContents();
            return json_decode($contents, true) ?? [];
        } catch (GuzzleException $e) {
            // Log error
            throw $e;
        }
    }
}
