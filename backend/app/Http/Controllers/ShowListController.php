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
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $list = new ShowList();
        $list->user_id = Auth::id();
        $list->title = $request->title;
        $list->description = $request->description;
        $list->shows = [];
        $list->save();

        return response()->json([
            'message' => 'List created successfully',
            'list' => $list
        ], 201);
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
        
        // Check if show already exists in list
        $exists = false;
        foreach ($shows as $show) {
            if ($show['tmdb_id'] === $request->tmdb_id) {
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
        $lists = ShowList::where('user_id', Auth::id())->get();
        return response()->json(['lists' => $lists], 200);
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

        $shows = array_filter($list->shows ?? [], function($show) use ($tmdbId) {
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
}
