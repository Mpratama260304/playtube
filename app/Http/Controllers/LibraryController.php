<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\WatchHistory;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function index()
    {
        $history = auth()->user()->watchHistory()
            ->with(['video.user'])
            ->latest('watched_at')
            ->get();

        $watchLater = auth()->user()->watchLater()
            ->with(['user'])
            ->get();

        $likedVideos = Video::published()
            ->with(['user'])
            ->whereHas('reactions', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('reaction', 'like');
            })
            ->take(10)
            ->get();

        $playlists = auth()->user()->playlists()->withCount('videos')->get();

        return view('library.index', compact('history', 'watchLater', 'likedVideos', 'playlists'));
    }

    public function history()
    {
        $history = auth()->user()->watchHistory()
            ->with(['video.user'])
            ->latest('watched_at')
            ->paginate(24);

        return view('library.history', compact('history'));
    }

    public function watchLater()
    {
        $videos = auth()->user()->watchLater()
            ->with(['user'])
            ->latest('watch_later.created_at')
            ->paginate(24);

        return view('library.watch-later', compact('videos'));
    }

    public function likedVideos()
    {
        $videos = Video::published()
            ->with(['user'])
            ->whereHas('reactions', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('reaction', 'like');
            })
            ->latest()
            ->paginate(24);

        return view('library.liked', compact('videos'));
    }

    public function subscriptions()
    {
        $channels = auth()->user()->subscriptions()
            ->withCount('videos')
            ->latest('subscriptions.created_at')
            ->paginate(24);

        return view('library.subscriptions', compact('channels'));
    }

    public function clearHistory()
    {
        auth()->user()->watchHistory()->delete();

        return back()->with('success', 'Watch history cleared.');
    }
}
