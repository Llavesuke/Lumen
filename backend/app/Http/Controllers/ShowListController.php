<?php

namespace App\Http\Controllers;

use App\Models\ShowList;
use App\Services\TMDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShowListController extends Controller
{
    protected $tmdbService;

    public function __construct(TMDBService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    public function createList(Request $request)
    {
        \Log::info('Create list request received', ['user_id' => Auth::id(), 'request_data' => $request->all()]);
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for create list request', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            \Log::info('Creating new list', ['title' => $request->title]);
            $list = new ShowList();
            $list->user_id = Auth::id();
            $list->title = $request->title;
            $list->description = $request->description;
            $list->is_public = $request->has('is_public') ? (bool)$request->is_public : false;
            $list->shows = [];
            $list->save();
            
            \Log::info('List created successfully', ['list_id' => $list->id]);
            return response()->json([
                'message' => 'List created successfully',
                'list' => $list
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating list: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to create list'], 500);
        }
    }

    public function addShowToList(Request $request, $listId)
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

        $list = ShowList::where('id', $listId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$list) {
            return response()->json(['error' => 'List not found or you do not have permission'], 404);
        }

        $shows = $list->shows ?? [];
        
        // Check if show already exists in list (validating both tmdb_id and type)
        $exists = false;
        foreach ($shows as $show) {
            if ($show['tmdb_id'] === $request->tmdb_id && $show['type'] === $request->type) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            return response()->json(['message' => 'Show is already in this list'], 200);
        }

        // Add new show to list
        $shows[] = ShowList::formatShowData($request->all());
        $list->shows = $shows;
        $list->save();

        return response()->json([
            'message' => 'Show added to list successfully',
            'show' => end($shows)
        ], 201);
    }

    public function getUserLists()
    {
        $startTime = microtime(true);
        
        \Log::info('Fetching user lists - User ID: ' . Auth::id());
        try {
            $lists = ShowList::where('user_id', Auth::id())->get();
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            \Log::info('Successfully retrieved user lists', [
                'count' => count($lists),
                'execution_time_ms' => $executionTime,
                'user_id' => Auth::id()
            ]);
            
            return response()->json(['lists' => $lists], 200);
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;
            
            \Log::error('Error fetching user lists: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => $executionTime
            ]);
            return response()->json(['error' => 'Failed to fetch lists'], 500);
        }
    }

    public function getList($listId)
    {
        $list = ShowList::where('id', $listId)
                ->where('user_id', Auth::id())
                ->first();

        if (!$list) {
            return response()->json(['error' => 'List not found'], 404);
        }

        return response()->json(['list' => $list], 200);
    }

    public function removeShowFromList(Request $request, $listId, $tmdbId)
    {
        $list = ShowList::where('id', $listId)
                ->where('user_id', Auth::id())
                ->first();

        if (!$list) {
            return response()->json(['error' => 'List not found'], 404);
        }
        
        // Get the show type from request query parameters
        $showType = $request->query('type');
        
        $shows = array_filter($list->shows ?? [], function($show) use ($tmdbId, $showType) {
            // If type is provided, filter by both tmdb_id and type
            if ($showType) {
                return !($show['tmdb_id'] === $tmdbId && $show['type'] === $showType);
            }
            // Otherwise, just filter by tmdb_id for backward compatibility
            return $show['tmdb_id'] !== $tmdbId;
        });

        $list->shows = array_values($shows);
        $list->save();

        return response()->json(['message' => 'Show removed from list'], 200);
    }

    public function deleteList($listId)
    {
        $list = ShowList::where('id', $listId)
                ->where('user_id', Auth::id())
                ->first();

        if (!$list) {
            return response()->json(['error' => 'List not found'], 404);
        }

        $list->delete();

        return response()->json(['message' => 'List deleted successfully'], 200);
    }
    
    public function getPublicLists()
    {
        $lists = ShowList::where('is_public', true)
                ->with('user:id,name')
                ->get();
                
        return response()->json(['lists' => $lists], 200);
    }
    
    public function togglePublicStatus(Request $request, $listId)
    {
        $list = ShowList::where('id', $listId)
                ->where('user_id', Auth::id())
                ->first();

        if (!$list) {
            return response()->json(['error' => 'List not found'], 404);
        }
        
        $list->is_public = !$list->is_public;
        $list->save();
        
        return response()->json([
            'message' => $list->is_public ? 'List is now public' : 'List is now private',
            'list' => $list
        ], 200);
    }
}
