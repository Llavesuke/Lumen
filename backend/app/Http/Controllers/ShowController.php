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
        $favorites[] = ShowList::formatShowData($request->all());
        
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
}
