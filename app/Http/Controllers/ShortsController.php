<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

class ShortsController extends Controller
{
    public function index()
    {
        $shorts = Video::published()
            ->with(['user'])
            ->shorts()
            ->latest('published_at')
            ->paginate(20);

        return view('shorts.index', compact('shorts'));
    }

    public function show(Video $video)
    {
        // Ensure it's a short video
        if (!$video->is_short) {
            return redirect()->route('video.watch', $video);
        }

        // Increment view count
        $video->increment('views_count');

        // Record watch history if user is logged in
        if (auth()->check()) {
            auth()->user()->watchHistory()->updateOrCreate(
                ['video_id' => $video->id],
                ['watched_at' => now()]
            );
        }

        // Load the video creator
        $video->load('user');

        // Load reactions for the authenticated user
        if (auth()->check()) {
            $video->load(['reactions' => function ($query) {
                $query->where('user_id', auth()->id());
            }]);
        }

        // Get more shorts for infinite scroll
        $moreShorts = Video::published()
            ->with(['user'])
            ->shorts()
            ->where('id', '!=', $video->id)
            ->inRandomOrder()
            ->take(10)
            ->get();

        return view('shorts.show', compact('video', 'moreShorts'));
    }
}
