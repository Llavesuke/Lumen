<?php

namespace App\Http\Controllers;

use App\Services\PlaydedeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ShowList;

class PlaydedeController extends Controller
{
    protected $playdedeService;

    public function __construct(PlaydedeService $playdedeService)
    {
        $this->playdedeService = $playdedeService;
        Log::info('PlaydedeController initialized');
    }

    public function getMovieSources(Request $request)
    {
        Log::info('Getting movie sources for request', $request->all());
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'tmdb_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Decode the title in case it comes URL encoded
            $decodedTitle = urldecode($request->title);
            $formattedTitle = ShowList::formatTitle($decodedTitle);
            Log::info('Original title: ' . $request->title);
            Log::info('Decoded title: ' . $decodedTitle);
            Log::info('Formatted title: ' . $formattedTitle);
            
            $sources = $this->playdedeService->getShowSources(
                $formattedTitle, 
                $request->tmdb_id, 
                'movie'
            );

            Log::info('Sources retrieved', ['count' => isset($sources['sources']) ? count($sources['sources']) : 0]);
            return response()->json($sources);
            
        } catch (\Exception $e) {
            Log::error('Error in getMovieSources: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function getSeriesEpisodeSources(Request $request)
    {
        Log::info('Getting series episode sources for request', $request->all());
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'tmdb_id' => 'required|string',
            'season' => 'required|integer|min:1',
            'episode' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $formattedTitle = ShowList::formatTitle($request->title);
            Log::info('Formatted title: ' . $formattedTitle);
            
            $sources = $this->playdedeService->getShowSources(
                $formattedTitle,
                $request->tmdb_id,
                'series',
                $request->season,
                $request->episode
            );

            Log::info('Sources retrieved', ['count' => isset($sources['sources']) ? count($sources['sources']) : 0]);
            return response()->json($sources);
            
        } catch (\Exception $e) {
            Log::error('Error in getSeriesEpisodeSources: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}