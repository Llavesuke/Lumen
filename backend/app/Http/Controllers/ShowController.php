<?php

namespace App\Http\Controllers;

use App\Models\ShowList;
use App\Services\TMDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShowController extends Controller
{
    protected $tmdbService;

    public function __construct(TMDBService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    /**
     * Get movie details by TMDB ID
     *
     * @param string $tmdbId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMovieDetails($tmdbId)
    {
        $cacheKey = 'movie_' . $tmdbId;
        
        return Cache::remember($cacheKey, 3600, function () use ($tmdbId) {
            $movieDetails = $this->tmdbService->getMovieDetails($tmdbId);

            if (!$movieDetails) {
                return response()->json(['error' => 'Movie not found'], 404);
            }

            return response()->json(['movie' => $movieDetails], 200);
        });
    }

    /**
     * Get series details with seasons and episodes by TMDB ID
     *
     * @param string $tmdbId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSeriesDetails($tmdbId)
    {
        $cacheKey = 'series_' . $tmdbId;
        
        return Cache::remember($cacheKey, 3600, function () use ($tmdbId) {
            $seriesDetails = $this->tmdbService->getSeriesDetails($tmdbId);

            if (!$seriesDetails) {
                return response()->json(['error' => 'Series not found'], 404);
            }

            return response()->json(['series' => $seriesDetails], 200);
        });
    }

    /**
     * Search shows by query text
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchShows(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = $request->query('query');
        Log::info('Searching shows with query: ' . $query);
        
        $results = $this->tmdbService->searchShows($query);
        Log::info('Found ' . count($results) . ' results for query: ' . $query);

        return response()->json([
            'results' => $results
        ], 200);
    }

    /**
     * Get shows by genre
     *
     * @param Request $request
     * @param string $genre
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShowsByGenre(Request $request, $genre)
    {
        Log::info('Getting shows by genre: ' . $genre);
        
        // Obtener resultados de películas y series usando el nuevo tipo 'all'
        $results = $this->tmdbService->getShowsByGenre($genre, 'all');
        
        return response()->json([
            'genre' => $genre,
            'results' => $results
        ], 200);
    }

    /**
     * Get shows by keyword
     *
     * @param Request $request
     * @param string $keyword
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShowsByKeyword(Request $request, $keyword)
    {
        Log::info('Getting shows by keyword: ' . $keyword);
        
        $results = $this->tmdbService->getShowsByKeyword($keyword);
        
        return response()->json([
            'keyword' => $keyword,
            'results' => $results
        ], 200);
    }

    /**
     * Add show to user favorites
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToFavorites(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tmdb_id' => 'required|string',
            'title' => 'required|string|max:255',
            'background_image' => 'required|url',
            'logo_image' => 'required|url',
            'type' => 'required|in:movie,series'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $favorites = $user->user_favorites ?? [];

        // Verificar si el show ya está en favoritos (validando tmdb_id y tipo)
        $exists = false;
        foreach ($favorites as $favorite) {
            if ($favorite['tmdb_id'] === $request->tmdb_id && $favorite['type'] === $request->type) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            return response()->json(['message' => 'Show is already in favorites'], 200);
        }

        // Añadir a favoritos
        $favorites[] = [
            'tmdb_id' => $request->tmdb_id,
            'title' => $request->title,
            'background_image' => $request->background_image,
            'logo_image' => $request->logo_image,
            'type' => $request->type
        ];
        
        // Actualizar favoritos en la base de datos
        $user->user_favorites = $favorites;
        $user->save();

        return response()->json([
            'message' => 'Show added to favorites successfully',
            'show' => end($favorites)
        ], 201);
    }

    /**
     * Remove show from user favorites
     *
     * @param int $tmdbId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromFavorites(Request $request, $tmdbId)
    {
        $user = Auth::user();
        $favorites = $user->user_favorites ?? [];
        
        // Get the show type from request query parameters
        $showType = $request->query('type');
        
        $favorites = array_filter($favorites, function($show) use ($tmdbId, $showType) {
            // If type is provided, filter by both tmdb_id and type
            if ($showType) {
                return !($show['tmdb_id'] === $tmdbId && $show['type'] === $showType);
            }
            // Otherwise, just filter by tmdb_id for backward compatibility
            return $show['tmdb_id'] !== $tmdbId;
        });

        $user->user_favorites = array_values($favorites);
        $user->save();

        return response()->json([
            'message' => 'Show removed from favorites successfully'
        ], 200);
    }

    /**
     * Get popular movies and series
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPopularShows()
    {
        $cacheKey = 'popular_shows';
        
        return Cache::remember($cacheKey, 3600, function () {
            // Get popular movies (3 items)
            $popularMovies = $this->tmdbService->getPopularShows('movie');
            $popularMovies = array_slice($popularMovies, 0, 3);
            
            // Get popular series (3 items)
            $popularSeries = $this->tmdbService->getPopularShows('tv');
            $popularSeries = array_slice($popularSeries, 0, 3);
            
            // Combine results
            $results = array_merge($popularMovies, $popularSeries);
            
            return response()->json([
                'results' => $results
            ], 200);
        });
    }

    /**
     * Get user favorites
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserFavorites()
    {
        $user = Auth::user();
        return response()->json([
            'favorites' => $user->user_favorites ?? []
        ], 200);
    }

    /**
     * Get all movies with pagination and filtering
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllMovies(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'year_from' => 'integer|min:1900|max:' . date('Y'),
            'year_to' => 'integer|min:1900|max:' . date('Y'),
            'genres' => 'string',
            'keywords' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $page = $request->query('page', 1);
        $yearFrom = $request->query('year_from');
        $yearTo = $request->query('year_to');
        $genres = $request->query('genres') ? explode(',', $request->query('genres')) : null;
        $keywords = $request->query('keywords') ? explode(',', $request->query('keywords')) : null;

        // Create a unique cache key based on all parameters
        $cacheKey = 'all_movies_' . $page;
        if ($yearFrom) $cacheKey .= '_from_' . $yearFrom;
        if ($yearTo) $cacheKey .= '_to_' . $yearTo;
        if ($genres) $cacheKey .= '_genres_' . implode('_', $genres);
        if ($keywords) $cacheKey .= '_keywords_' . implode('_', $keywords);

        return Cache::remember($cacheKey, 3600, function () use ($page, $yearFrom, $yearTo, $genres, $keywords) {
            $results = $this->tmdbService->getAllMovies($page, $yearFrom, $yearTo, $genres, $keywords);
            
            return response()->json([
                'page' => $results['page'],
                'total_pages' => $results['total_pages'],
                'total_results' => $results['total_results'],
                'results' => $results['results']
            ], 200);
        });
    }

    /**
     * Get trending movies for a time window
     *
     * @param Request $request
     * @param string $timeWindow
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrendingMovies(Request $request, $timeWindow = 'week')
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $page = $request->query('page', 1);
        $cacheKey = 'trending_movies_' . $timeWindow . '_' . $page;

        return Cache::remember($cacheKey, 3600, function () use ($page, $timeWindow) {
            $results = $this->tmdbService->getTrending('movie', $timeWindow, $page);
            
            return response()->json([
                'page' => $results['page'],
                'total_pages' => $results['total_pages'],
                'total_results' => $results['total_results'],
                'results' => $results['results']
            ], 200);
        });
    }

    /**
     * Get trending TV shows for a time window
     *
     * @param Request $request
     * @param string $timeWindow
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrendingTvShows(Request $request, $timeWindow = 'week')
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $page = $request->query('page', 1);
        $cacheKey = 'trending_tv_' . $timeWindow . '_' . $page;

        return Cache::remember($cacheKey, 3600, function () use ($page, $timeWindow) {
            $results = $this->tmdbService->getTrending('tv', $timeWindow, $page);
            
            return response()->json([
                'page' => $results['page'],
                'total_pages' => $results['total_pages'],
                'total_results' => $results['total_results'],
                'results' => $results['results']
            ], 200);
        });
    }

    /**
     * Get all series with pagination and filtering
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSeries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'year_from' => 'integer|min:1900|max:' . date('Y'),
            'year_to' => 'integer|min:1900|max:' . date('Y'),
            'genres' => 'string',
            'keywords' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $page = $request->query('page', 1);
        $yearFrom = $request->query('year_from');
        $yearTo = $request->query('year_to');
        $genres = $request->query('genres') ? explode(',', $request->query('genres')) : null;
        $keywords = $request->query('keywords') ? explode(',', $request->query('keywords')) : null;

        // Create a unique cache key based on all parameters
        $cacheKey = 'all_series_' . $page;
        if ($yearFrom) $cacheKey .= '_from_' . $yearFrom;
        if ($yearTo) $cacheKey .= '_to_' . $yearTo;
        if ($genres) $cacheKey .= '_genres_' . implode('_', $genres);
        if ($keywords) $cacheKey .= '_keywords_' . implode('_', $keywords);

        return Cache::remember($cacheKey, 3600, function () use ($page, $yearFrom, $yearTo, $genres, $keywords) {
            $results = $this->tmdbService->getAllSeries($page, $yearFrom, $yearTo, $genres, $keywords);
            
            return response()->json([
                'page' => $results['page'],
                'total_pages' => $results['total_pages'],
                'total_results' => $results['total_results'],
                'results' => $results['results']
            ], 200);
        });
    }
}
