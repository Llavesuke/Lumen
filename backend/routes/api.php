<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\ShowListController;
use App\Http\Controllers\PlaydedeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Version 1 Routes
Route::prefix('v1')->group(function () {
    // Authentication Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Public show endpoints
    Route::get('/shows/search', [ShowController::class, 'searchShows']);
    Route::get('/shows/genre/{genre}', [ShowController::class, 'getShowsByGenre']);
    Route::get('/movies/{tmdbId}', [ShowController::class, 'getMovieDetails']);
    Route::get('/series/{tmdbId}', [ShowController::class, 'getSeriesDetails']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // User Lists
        Route::prefix('lists')->group(function () {
            Route::post('/', [ShowListController::class, 'createList']);
            Route::get('/', [ShowListController::class, 'getUserLists']);
            Route::get('/public', [ShowListController::class, 'getPublicLists']);
            Route::get('/{listId}', [ShowListController::class, 'getList']);
            Route::post('/{listId}/shows', [ShowListController::class, 'addShowToList']);
            Route::post('/{listId}/toggle-public', [ShowListController::class, 'togglePublicStatus']);
            Route::delete('/{listId}/shows/{tmdbId}', [ShowListController::class, 'removeShowFromList']);
            Route::delete('/{listId}', [ShowListController::class, 'deleteList']);
        });

        // User Favorites
        Route::prefix('favorites')->group(function () {
            Route::get('/', [ShowController::class, 'getUserFavorites']);
            Route::post('/', [ShowController::class, 'addToFavorites']);
            Route::delete('/{tmdbId}', [ShowController::class, 'removeFromFavorites']);
        });
    });

    // Playdede Routes
    Route::prefix('playdede')->group(function () {
        Route::get('/movie', [PlaydedeController::class, 'getMovieSources']);
        Route::get('/series', [PlaydedeController::class, 'getSeriesEpisodeSources']);
    });
});