<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TMDBService
{
    protected $apiKey;
    protected $baseUrl;
    protected $imageBaseUrl;
    protected $headers;

    public function __construct()
    {
        $this->apiKey = config('services.tmdb.api_key');
        $this->baseUrl = 'https://api.themoviedb.org/3';
        $this->imageBaseUrl = 'https://image.tmdb.org/t/p/original';
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];
        
        if (empty($this->apiKey)) {
            Log::error('TMDB API key is not configured');
        } else {
            Log::info('TMDB Service initialized with API key');
        }
    }

    /**
     * Search for movies and TV shows by query
     * 
     * @param string $query
     * @return array
     */
    public function searchShows($query)
    {
        try {
            Log::info('Searching shows with query: ' . $query);
            
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/search/multi", [
                    'query' => $query,
                    'include_adult' => false,
                    'language' => 'es-ES'
                ]);
            
            Log::info('TMDB API Response Status: ' . $response->status());
            Log::info('TMDB API Response: ' . $response->body());
            
            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                Log::info('Search results count: ' . count($results));
                $formatted = $this->formatSearchResults($results);
                Log::info('Formatted results count: ' . count($formatted));
                return $formatted;
            } else {
                Log::error('TMDB API search error: ' . $response->status() . ' - ' . $response->body());
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('TMDB API search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get shows by genre
     * 
     * @param string $genre
     * @param string $type
     * @return array
     */
    public function getShowsByGenre($genre, $type = 'movie')
    {
        try {
            Log::info("Getting shows by genre: {$genre}, type: {$type}");
            
            if (strtolower($genre) === 'popular') {
                return $this->getPopularShows($type);
            }

            // Obtener los resultados según el tipo
            if ($type === 'all') {
                $movieResults = $this->getShowsByGenreType($genre, 'movie');
                $seriesResults = $this->getShowsByGenreType($genre, 'tv');
                return array_merge($movieResults, $seriesResults);
            }
            
            return $this->getShowsByGenreType($genre, $type);
            
        } catch (\Exception $e) {
            Log::error('TMDB API genre search error: ' . $e->getMessage());
            return [];
        }
    }

    private function getShowsByGenreType($genre, $type)
    {
        try {
            // Get genre ID
            $genreList = $this->getGenreList($type);
            $genreId = null;
            
            foreach ($genreList as $genreItem) {
                if (strtolower($genreItem['name']) === strtolower($genre)) {
                    $genreId = $genreItem['id'];
                    break;
                }
            }
            
            if (!$genreId) {
                Log::warning("Genre ID not found for: {$genre}");
                return [];
            }
            
            $endpoint = $type === 'movie' ? 'discover/movie' : 'discover/tv';
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/{$endpoint}", [
                    'with_genres' => $genreId,
                    'sort_by' => 'popularity.desc',
                    'language' => 'es-ES',
                    'page' => 1
                ]);
            
            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                return $this->formatSearchResults($results, $type);
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error("Error getting shows by genre type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get popular shows
     * 
     * @param string $type
     * @return array
     */
    public function getPopularShows($type = 'movie')
    {
        try {
            $endpoint = $type === 'movie' ? 'movie/popular' : 'tv/popular';
            
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/{$endpoint}", [
                    'language' => 'es-ES'
                ]);
            
            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                return $this->formatSearchResults($results, $type);
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('TMDB API popular shows error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get movie details
     * 
     * @param string $tmdbId
     * @return array|null
     */
    public function getMovieDetails($tmdbId)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/movie/{$tmdbId}", [
                    'append_to_response' => 'release_dates',
                    'language' => 'es-ES'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'tmdb_id' => $data['id'],
                    'title' => $data['title'],
                    'release_year' => date('Y', strtotime($data['release_date'] ?? 'now')),
                    'rating' => round($data['vote_average'] * 10), // Convert from 0-10 to 0-100 and round to whole number
                    'age_classification' => $this->extractAgeRating($data),
                    'background_image' => $this->imageBaseUrl . $data['backdrop_path'],
                    'logo_image' => $this->getShowLogo($tmdbId, 'movie'),
                    'overview' => (isset($data['overview']) && !empty(trim($data['overview']))) ? $data['overview'] : 'No hay una sinopsis disponible',
                    'type' => 'movie'
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('TMDB API movie details error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get TV series details with seasons and episodes
     * 
     * @param string $tmdbId
     * @return array|null
     */
    public function getSeriesDetails($tmdbId)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/tv/{$tmdbId}", [
                    'append_to_response' => 'content_ratings',
                    'language' => 'es-ES'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $seasons = [];
                
                // Get details for each season
                foreach ($data['seasons'] as $season) {
                    if ($season['season_number'] > 0) { // Skip specials (season 0)
                        $seasonDetails = $this->getSeasonDetails($tmdbId, $season['season_number']);
                        if ($seasonDetails) {
                            $seasons[] = $seasonDetails;
                        }
                    }
                }
                
                return [
                    'tmdb_id' => $data['id'],
                    'title' => $data['name'],
                    'release_year' => date('Y', strtotime($data['first_air_date'] ?? 'now')),
                    'rating' => round($data['vote_average'] * 10),
                    'age_classification' => $this->extractTvContentRating($data),
                    'background_image' => $this->imageBaseUrl . $data['backdrop_path'],
                    'logo_image' => $this->getShowLogo($tmdbId, 'tv'),
                    'overview' => (isset($data['overview']) && !empty(trim($data['overview']))) ? $data['overview'] : 'No hay una sinopsis disponible',
                    'seasons_count' => count($seasons),
                    'seasons' => $seasons,
                    'type' => 'series'
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('TMDB API series details error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get season details with episodes
     * 
     * @param string $tmdbId
     * @param int $seasonNumber
     * @return array|null
     */
    private function getSeasonDetails($tmdbId, $seasonNumber)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$seasonNumber}", [
                    'language' => 'es-ES'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $episodes = [];
                
                foreach ($data['episodes'] as $episode) {
                    $episodes[] = [
                        'name' => $episode['name'],
                        'episode_number' => $episode['episode_number'],
                        'rating' => round($data['vote_average'] * 10),
                        'runtime' => $episode['runtime'] ?? 0,
                        'air_date' => $this->formatDate($episode['air_date'] ?? null),
                        'still_image' => $episode['still_path'] ? $this->imageBaseUrl . $episode['still_path'] : null
                    ];
                }
                
                return [
                    'season_number' => $seasonNumber,
                    'name' => $data['name'],
                    'episodes' => $episodes
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('TMDB API season details error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get show logo from TMDb images
     * 
     * @param string $tmdbId
     * @param string $type
     * @return string|null
     */
    private function getShowLogo($tmdbId, $type)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/{$type}/{$tmdbId}/images", [
                    'include_image_language' => 'es,en,null',
                    'language' => 'es-ES'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Try to find logo images first
                if (!empty($data['logos'])) {
                    $englishLogos = array_filter($data['logos'], function($img) {
                        return $img['iso_639_1'] === 'en';
                    });
                    
                    if (!empty($englishLogos)) {
                        $logo = reset($englishLogos);
                        return $this->imageBaseUrl . $logo['file_path'];
                    }
                    
                    // If no English logo found, use the first logo
                    if (!empty($data['logos'][0])) {
                        return $this->imageBaseUrl . $data['logos'][0]['file_path'];
                    }
                }
                
                // No fallback to poster - we want to return null if no logo is found
                // so the frontend can display the title text instead
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('TMDB API logo search error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format search results to a consistent structure
     * 
     * @param array $results
     * @param string|null $type
     * @return array
     */
    private function formatSearchResults($results, $type = null)
    {
        $formatted = [];
        Log::info('Formatting ' . count($results) . ' results');
        
        foreach ($results as $result) {
            try {
                $mediaType = $type ?: ($result['media_type'] ?? null);
                
                // Skip if not a movie or TV show
                if (!in_array($mediaType, ['movie', 'tv'])) {
                    continue;
                }
                
                $title = $mediaType === 'movie' ? ($result['title'] ?? '') : ($result['name'] ?? '');
                $tmdbId = (string)($result['id'] ?? '');
                
                if (empty($tmdbId) || empty($title)) {
                    Log::info("Skipping result - Missing required data");
                    continue;
                }

                Log::info("Processing result - Type: {$mediaType}, Title: {$title}, ID: {$tmdbId}");
                
                // Ensure proper UTF-8 encoding
                $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
                
                // Get backdrop and logo paths
                $backdropPath = $result['backdrop_path'] ?? null;
                $posterPath = $result['poster_path'] ?? null;
                
                // Use poster path as fallback if backdrop is not available
                $backgroundPath = $backdropPath ?: $posterPath;
                
                if (!$backgroundPath) {
                    Log::info("Skipping {$title} - No background image available");
                    continue;
                }

                $showData = [
                    'tmdb_id' => $tmdbId,
                    'title' => $title,
                    'formatted_title' => \App\Models\ShowList::formatTitle($title),
                    'background_image' => $this->imageBaseUrl . $backgroundPath,
                    'logo_image' => $this->getShowLogo($tmdbId, $mediaType === 'movie' ? 'movie' : 'tv'),
                    'type' => $mediaType === 'movie' ? 'movie' : 'series'
                ];

                // Validate UTF-8 encoding for all string values
                array_walk_recursive($showData, function(&$value) {
                    if (is_string($value)) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                });

                $formatted[] = $showData;
                
            } catch (\Exception $e) {
                Log::error("Error processing result: " . $e->getMessage());
                continue;
            }
        }
        
        Log::info('Formatted ' . count($formatted) . ' results successfully');
        return $formatted;
    }

    /**
     * Extract age rating from movie release dates
     * 
     * @param array $movieData
     * @return string
     */
    private function extractAgeRating($movieData)
    {
        // Try to find US rating first
        if (!empty($movieData['release_dates']['results'])) {
            foreach ($movieData['release_dates']['results'] as $country) {
                if ($country['iso_3166_1'] === 'US' && !empty($country['release_dates'])) {
                    foreach ($country['release_dates'] as $release) {
                        if (!empty($release['certification'])) {
                            return $release['certification'];
                        }
                    }
                }
            }
        }
        
        // Default value if not found
        return 'NR'; // Not Rated
    }

    /**
     * Extract TV content rating
     * 
     * @param array $tvData
     * @return string
     */
    private function extractTvContentRating($tvData)
    {
        // Try to find US rating first
        if (!empty($tvData['content_ratings']['results'])) {
            foreach ($tvData['content_ratings']['results'] as $country) {
                if ($country['iso_3166_1'] === 'US') {
                    return $country['rating'] ?? 'NR';
                }
            }
        }
        
        // Default value if not found
        return 'NR'; // Not Rated
    }

    /**
     * Format date to DD/MM/YYYY
     * 
     * @param string|null $date
     * @return string
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return 'Unknown';
        }
        
        return date('d/m/Y', strtotime($date));
    }

    /**
     * Get genre list by type
     *
     * @param string $type
     * @return array
     */
    private function getGenreList($type = 'movie')
    {
        $endpoint = $type === 'movie' ? 'genre/movie/list' : 'genre/tv/list';
        
        try {
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/{$endpoint}", [
                    'language' => 'es-ES'
                ]);
            
            if ($response->successful()) {
                return $response->json()['genres'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('TMDB API genre list error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get shows by keyword
     * 
     * @param string $keyword
     * @return array
     */
    public function getShowsByKeyword($keyword)
    {
        try {
            Log::info("Getting shows by keyword: {$keyword}");
            
            // Get movies by keyword
            $movieResponse = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/discover/movie", [
                    'with_keywords' => $this->getKeywordId($keyword),
                    'sort_by' => 'popularity.desc',
                    'language' => 'es-ES',
                    'page' => 1
                ]);
            
            // Get TV shows by keyword
            $tvResponse = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/discover/tv", [
                    'with_keywords' => $this->getKeywordId($keyword),
                    'sort_by' => 'popularity.desc',
                    'language' => 'es-ES',
                    'page' => 1
                ]);
            
            $results = [];
            
            if ($movieResponse->successful()) {
                $movieResults = $movieResponse->json()['results'] ?? [];
                $results = array_merge($results, $this->formatSearchResults($movieResults, 'movie'));
            }
            
            if ($tvResponse->successful()) {
                $tvResults = $tvResponse->json()['results'] ?? [];
                $results = array_merge($results, $this->formatSearchResults($tvResults, 'tv'));
            }
            
            return $results;
            
        } catch (\Exception $e) {
            Log::error('TMDB API keyword search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get keyword ID from TMDB API
     * 
     * @param string $keyword
     * @return int|null
     */
    private function getKeywordId($keyword)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/search/keyword", [
                    'query' => $keyword,
                    'page' => 1
                ]);
            
            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                if (!empty($results)) {
                    return $results[0]['id'];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('TMDB API keyword ID search error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all movies with pagination and filtering
     *
     * @param int $page
     * @param int|null $yearFrom
     * @param int|null $yearTo
     * @param array|null $genres
     * @param array|null $keywords
     * @return array
     */
    public function getAllMovies($page = 1, $yearFrom = null, $yearTo = null, $genres = null, $keywords = null)
    {
        try {
            $params = [
                'language' => 'es-ES',
                'page' => $page,
                'sort_by' => 'popularity.desc',
                'include_adult' => false
            ];

            // Add year range filter
            if ($yearFrom) {
                $params['primary_release_date.gte'] = $yearFrom . '-01-01';
            }
            if ($yearTo) {
                $params['primary_release_date.lte'] = $yearTo . '-12-31';
            }

            // Add genre filter
            if ($genres) {
                $genreIds = [];
                // Check if the genre values are already IDs (numeric) or names
                $containsNumericIds = false;
                foreach ($genres as $genre) {
                    if (is_numeric($genre)) {
                        $containsNumericIds = true;
                        break;
                    }
                }
                
                if ($containsNumericIds) {
                    // If genres are already IDs, use them directly
                    $genreIds = $genres;
                } else {
                    // If genres are names, convert to IDs
                    $genreList = $this->getGenreList('movie');
                    foreach ($genres as $genreName) {
                        foreach ($genreList as $genre) {
                            if (strtolower($genre['name']) === strtolower($genreName)) {
                                $genreIds[] = $genre['id'];
                                break;
                            }
                        }
                    }
                }
                
                if (!empty($genreIds)) {
                    $params['with_genres'] = implode(',', $genreIds);
                }
                
                // Log the genre filter being applied
                Log::info('Applying genre filter with IDs: ' . implode(',', $genreIds));
            }

            // Add keyword filter
            if ($keywords) {
                $keywordIds = [];
                foreach ($keywords as $keyword) {
                    $keywordResponse = Http::withHeaders($this->headers)
                        ->get("{$this->baseUrl}/search/keyword", [
                            'query' => $keyword,
                            'language' => 'es-ES'
                        ]);
                    if ($keywordResponse->successful()) {
                        $keywordResults = $keywordResponse->json()['results'] ?? [];
                        if (!empty($keywordResults)) {
                            $keywordIds[] = $keywordResults[0]['id'];
                        }
                    }
                }
                if (!empty($keywordIds)) {
                    $params['with_keywords'] = implode(',', $keywordIds);
                }
            }

            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/discover/movie", $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'page' => $data['page'],
                    'total_pages' => $data['total_pages'],
                    'total_results' => $data['total_results'],
                    'results' => $this->formatSearchResults($data['results'], 'movie')
                ];
            }

            return [
                'page' => 1,
                'total_pages' => 1,
                'total_results' => 0,
                'results' => []
            ];
        } catch (\Exception $e) {
            Log::error('Error getting all movies: ' . $e->getMessage());
            return [
                'page' => 1,
                'total_pages' => 1,
                'total_results' => 0,
                'results' => []
            ];
        }
    }

    /**
     * Get trending content by media type and time window
     * 
     * @param string $mediaType (movie, tv, person, all)
     * @param string $timeWindow (day, week)
     * @param int $page
     * @return array
     */
    public function getTrending($mediaType = 'movie', $timeWindow = 'week', $page = 1)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/trending/{$mediaType}/{$timeWindow}", [
                    'language' => 'es-ES',
                    'page' => $page
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'page' => $data['page'],
                    'total_pages' => $data['total_pages'],
                    'total_results' => $data['total_results'],
                    'results' => $this->formatSearchResults($data['results'], $mediaType === 'tv' ? 'tv' : 'movie')
                ];
            }
            
            return [
                'page' => 1,
                'total_pages' => 1,
                'total_results' => 0,
                'results' => []
            ];
        } catch (\Exception $e) {
            Log::error('TMDB API trending error: ' . $e->getMessage());
            return [
                'page' => 1,
                'total_pages' => 1,
                'total_results' => 0,
                'results' => []
            ];
        }
    }

    /**
     * Get all series with pagination and filtering
     *
     * @param int $page
     * @param int|null $yearFrom
     * @param int|null $yearTo
     * @param array|null $genres
     * @param array|null $keywords
     * @return array
     */
    public function getAllSeries($page = 1, $yearFrom = null, $yearTo = null, $genres = null, $keywords = null)
    {
        try {
            $params = [
                'language' => 'es-ES',
                'page' => $page,
                'sort_by' => 'popularity.desc',
                'include_adult' => false
            ];

            // Add year range filter
            if ($yearFrom) {
                $params['first_air_date.gte'] = $yearFrom . '-01-01';
            }
            if ($yearTo) {
                $params['first_air_date.lte'] = $yearTo . '-12-31';
            }

            // Add genre filter
            if ($genres) {
                $genreIds = [];
                // Check if the genre values are already IDs (numeric) or names
                $containsNumericIds = false;
                foreach ($genres as $genre) {
                    if (is_numeric($genre)) {
                        $containsNumericIds = true;
                        break;
                    }
                }
                
                if ($containsNumericIds) {
                    // If genres are already IDs, use them directly
                    $genreIds = $genres;
                } else {
                    // If genres are names, convert to IDs
                    $genreList = $this->getGenreList('tv');
                    foreach ($genres as $genreName) {
                        foreach ($genreList as $genre) {
                            if (strtolower($genre['name']) === strtolower($genreName)) {
                                $genreIds[] = $genre['id'];
                                break;
                            }
                        }
                    }
                }
                
                if (!empty($genreIds)) {
                    $params['with_genres'] = implode(',', $genreIds);
                }
                
                // Log the genre filter being applied
                Log::info('Applying genre filter with IDs: ' . implode(',', $genreIds));
            }

            // Add keyword filter
            if ($keywords) {
                $keywordIds = [];
                foreach ($keywords as $keyword) {
                    $keywordResponse = Http::withHeaders($this->headers)
                        ->get("{$this->baseUrl}/search/keyword", [
                            'query' => $keyword,
                            'language' => 'es-ES'
                        ]);
                    if ($keywordResponse->successful()) {
                        $keywordResults = $keywordResponse->json()['results'] ?? [];
                        if (!empty($keywordResults)) {
                            $keywordIds[] = $keywordResults[0]['id'];
                        }
                    }
                }
                if (!empty($keywordIds)) {
                    $params['with_keywords'] = implode(',', $keywordIds);
                }
            }

            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}/discover/tv", $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'page' => $data['page'],
                    'total_pages' => $data['total_pages'],
                    'total_results' => $data['total_results'],
                    'results' => $this->formatSearchResults($data['results'], 'tv')
                ];
            }

            return [
                'page' => 1,
                'total_pages' => 1,
                'total_results' => 0,
                'results' => []
            ];
        } catch (\Exception $e) {
            Log::error('Error getting all series: ' . $e->getMessage());
            return [
                'page' => 1,
                'total_pages' => 1,
                'total_results' => 0,
                'results' => []
            ];
        }
    }
}