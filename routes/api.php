<?php

use App\Http\Controllers\Api\VideoApiController;
use App\Http\Controllers\Api\SearchApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API Routes
Route::prefix('v1')->group(function () {
    // Videos
    Route::get('/videos', [VideoApiController::class, 'index']);
    Route::get('/videos/{video}', [VideoApiController::class, 'show']);
    Route::get('/videos/{video}/related', [VideoApiController::class, 'related']);
    
    // Search
    Route::get('/search', [SearchApiController::class, 'search']);
    Route::get('/search/suggestions', [SearchApiController::class, 'suggestions']);
    
    // Categories
    Route::get('/categories', function () {
        return \App\Models\Category::all();
    });
});

// Authenticated API Routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Video interactions
    Route::post('/videos/{video}/view', [VideoApiController::class, 'recordView']);
    Route::post('/videos/{video}/react', [VideoApiController::class, 'react']);
    Route::post('/videos/{video}/comment', [VideoApiController::class, 'comment']);
    Route::get('/videos/{video}/comments', [VideoApiController::class, 'comments']);
    
    // User's videos
    Route::get('/my/videos', [VideoApiController::class, 'myVideos']);
    Route::post('/my/videos', [VideoApiController::class, 'store']);
    Route::put('/my/videos/{video}', [VideoApiController::class, 'update']);
    Route::delete('/my/videos/{video}', [VideoApiController::class, 'destroy']);
    
    // Subscriptions
    Route::post('/channels/{user}/subscribe', function (\App\Models\User $user, Request $request) {
        $authUser = $request->user();
        if ($authUser->id === $user->id) {
            return response()->json(['message' => 'Cannot subscribe to yourself'], 422);
        }
        
        $subscription = $authUser->subscriptions()->where('channel_id', $user->id)->first();
        if ($subscription) {
            $subscription->delete();
            return response()->json(['subscribed' => false]);
        }
        
        $authUser->subscriptions()->create(['channel_id' => $user->id]);
        return response()->json(['subscribed' => true]);
    });
    
    // Watch history
    Route::get('/my/history', function (Request $request) {
        return $request->user()->watchHistory()
            ->with('video')
            ->latest()
            ->paginate(20);
    });
    
    // Watch later
    Route::get('/my/watch-later', function (Request $request) {
        return $request->user()->watchLater()
            ->with('video')
            ->latest()
            ->paginate(20);
    });
    
    Route::post('/videos/{video}/watch-later', function (\App\Models\Video $video, Request $request) {
        $user = $request->user();
        $existing = $user->watchLater()->where('video_id', $video->id)->first();
        
        if ($existing) {
            $existing->delete();
            return response()->json(['saved' => false]);
        }
        
        $user->watchLater()->create(['video_id' => $video->id]);
        return response()->json(['saved' => true]);
    });
});
