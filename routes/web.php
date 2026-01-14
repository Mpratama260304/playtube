<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ShortsController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\Studio\StudioController;
use App\Http\Controllers\Studio\ChunkedUploadController;
use App\Http\Controllers\Studio\EmbedController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VideoStreamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check (for Docker/Kubernetes)
|--------------------------------------------------------------------------
| NOTE: This endpoint must NOT hit DB/cache to avoid false failures
*/
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/trending', [HomeController::class, 'trending'])->name('trending');
Route::get('/latest', [HomeController::class, 'latest'])->name('latest');

// Video Watch
Route::get('/watch/{video:slug}', [VideoController::class, 'watch'])->name('video.watch');
Route::get('/embed/{video:slug}', [VideoController::class, 'embed'])->name('video.embed');
Route::get('/video/{video}/comments', [VideoController::class, 'comments'])->name('video.comments');

// Video Streaming with Range support (for smooth playback/seeking)
Route::get('/stream/{video:uuid}', [VideoStreamController::class, 'stream'])->name('video.stream');

// Thumbnail proxy to Go server (high performance)
Route::get('/thumb/{uuid}', [VideoStreamController::class, 'thumbnail'])->name('video.thumbnail');

// Search
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');

// Shorts
Route::get('/shorts', [ShortsController::class, 'index'])->name('shorts.index');
Route::get('/shorts/{video:slug}', [ShortsController::class, 'show'])->name('shorts.show');
Route::post('/shorts/load-more', [ShortsController::class, 'loadMore'])->name('shorts.loadMore');

// Channel
Route::get('/channel/{user:username}', [ChannelController::class, 'show'])->name('channel.show');
Route::get('/channel/{user:username}/videos', [ChannelController::class, 'videos'])->name('channel.videos');
Route::get('/channel/{user:username}/shorts', [ChannelController::class, 'shorts'])->name('channel.shorts');
Route::get('/channel/{user:username}/playlists', [ChannelController::class, 'playlists'])->name('channel.playlists');
Route::get('/channel/{user:username}/about', [ChannelController::class, 'about'])->name('channel.about');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    // Video Interactions
    Route::post('/video/{video}/react', [VideoController::class, 'react'])->name('video.react');
    Route::post('/video/{video}/comment', [VideoController::class, 'comment'])->name('video.comment');
    Route::post('/video/{video}/watch-later', [VideoController::class, 'toggleWatchLater'])->name('video.watch-later');
    Route::delete('/comment/{comment}', [VideoController::class, 'deleteComment'])->name('comment.delete');

    // Subscribe
    Route::post('/channel/{user:username}/subscribe', [ChannelController::class, 'subscribe'])->name('channel.subscribe');

    // Studio (Creator Dashboard)
    Route::prefix('studio')->name('studio.')->group(function () {
        Route::get('/', [StudioController::class, 'dashboard'])->name('dashboard');
        Route::get('/upload', [StudioController::class, 'upload'])->name('upload');
        Route::post('/upload', [StudioController::class, 'store'])->name('store');
        Route::post('/upload/ajax', [StudioController::class, 'ajaxStore'])->name('store.ajax');
        
        // Embed video routes
        Route::get('/embed', [EmbedController::class, 'create'])->name('embed');
        Route::post('/embed/parse', [EmbedController::class, 'parse'])->name('embed.parse');
        Route::post('/embed/store', [EmbedController::class, 'store'])->name('embed.store');
        
        // Chunked upload endpoints (for large files / proxy bypass)
        Route::post('/upload/chunked/init', [ChunkedUploadController::class, 'init'])->name('chunked.init');
        Route::post('/upload/chunked/chunk', [ChunkedUploadController::class, 'chunk'])->name('chunked.chunk');
        Route::post('/upload/chunked/complete', [ChunkedUploadController::class, 'complete'])->name('chunked.complete');
        Route::post('/upload/chunked/abort', [ChunkedUploadController::class, 'abort'])->name('chunked.abort');
        
        Route::get('/videos', [StudioController::class, 'videos'])->name('videos');
        Route::get('/videos/{video}/edit', [StudioController::class, 'edit'])->name('edit');
        Route::get('/videos/{video}/status', [StudioController::class, 'uploadStatus'])->name('status');
        Route::get('/videos/{video}/status-detail', [StudioController::class, 'videoStatus'])->name('video-status');
        Route::post('/videos/{video}/retry', [StudioController::class, 'retryProcessing'])->name('retry');
        Route::put('/videos/{video}', [StudioController::class, 'update'])->name('update');
        Route::delete('/videos/{video}', [StudioController::class, 'destroy'])->name('destroy');
        Route::get('/analytics', [StudioController::class, 'analytics'])->name('analytics');
        Route::get('/processing-status', [StudioController::class, 'processingStatus'])->name('processing-status');
    });

    // Library
    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('/history', [LibraryController::class, 'history'])->name('library.history');
    Route::delete('/history', [LibraryController::class, 'clearHistory'])->name('library.clear-history');
    Route::get('/watch-later', [LibraryController::class, 'watchLater'])->name('library.watch-later');
    Route::get('/liked-videos', [LibraryController::class, 'likedVideos'])->name('library.liked');
    Route::get('/subscriptions', [LibraryController::class, 'subscriptions'])->name('library.subscriptions');

    // Playlists
    Route::get('/playlists', [PlaylistController::class, 'index'])->name('playlists.index');
    Route::get('/playlist/{playlist:slug}', [PlaylistController::class, 'show'])->name('playlist.show');
    Route::post('/playlists', [PlaylistController::class, 'store'])->name('playlists.store');
    Route::put('/playlists/{playlist}', [PlaylistController::class, 'update'])->name('playlists.update');
    Route::delete('/playlists/{playlist}', [PlaylistController::class, 'destroy'])->name('playlists.destroy');
    Route::post('/playlists/{playlist}/videos', [PlaylistController::class, 'addVideo'])->name('playlists.add-video');
    Route::delete('/playlists/{playlist}/videos/{video}', [PlaylistController::class, 'removeVideo'])->name('playlists.remove-video');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/conversation/start', [MessageController::class, 'startConversation'])->name('messages.start');

    // Reports
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');

    // Creator Request
    Route::get('/creator/status', [App\Http\Controllers\CreatorRequestController::class, 'status'])->name('creator.status');
    Route::post('/creator/request', [App\Http\Controllers\CreatorRequestController::class, 'store'])->name('creator.request');
    Route::delete('/creator/request', [App\Http\Controllers\CreatorRequestController::class, 'cancel'])->name('creator.cancel');

    // User Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/channel', [SettingsController::class, 'updateChannel'])->name('settings.channel');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::delete('/settings/account', [SettingsController::class, 'deleteAccount'])->name('settings.delete-account');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Dashboard redirect to studio
Route::get('/dashboard', function () {
    return redirect()->route('studio.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
