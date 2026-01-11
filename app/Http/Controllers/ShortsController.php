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

        // Load the video creator and reactions
        $video->load('user');
        if (auth()->check()) {
            $video->load(['reactions' => function ($query) {
                $query->where('user_id', auth()->id());
            }]);
        }

        // Get more shorts for scroll feed (excluding current video)
        // Order by published_at to maintain consistent order
        $moreShorts = Video::published()
            ->with(['user', 'reactions' => function($query) {
                if (auth()->check()) {
                    $query->where('user_id', auth()->id());
                }
            }])
            ->shorts()
            ->where('id', '!=', $video->id)
            ->latest('published_at')
            ->take(15)
            ->get();

        // Combine current video with more shorts for the feed
        $allShorts = collect([$video])->merge($moreShorts);

        return view('shorts.show', compact('video', 'moreShorts', 'allShorts'));
    }
    
    /**
     * API endpoint to load more shorts for infinite scroll
     */
    public function loadMore(Request $request)
    {
        $excludeIds = $request->input('exclude', []);
        
        $shorts = Video::published()
            ->with(['user', 'reactions' => function($query) {
                if (auth()->check()) {
                    $query->where('user_id', auth()->id());
                }
            }])
            ->shorts()
            ->whereNotIn('id', $excludeIds)
            ->latest('published_at')
            ->take(10)
            ->get()
            ->map(function($short) {
                return [
                    'id' => $short->id,
                    'slug' => $short->slug,
                    'uuid' => $short->uuid,
                    'title' => $short->title,
                    'thumbnail_url' => $short->thumbnail_url,
                    'stream_url' => route('video.stream', $short->uuid),
                    'views_count' => $short->views_count,
                    'likes_count' => $short->likes_count ?? 0,
                    'dislikes_count' => $short->dislikes_count ?? 0,
                    'comments_count' => $short->comments_count ?? 0,
                    'published_at' => $short->published_at?->diffForHumans() ?? $short->created_at->diffForHumans(),
                    'user' => [
                        'id' => $short->user->id,
                        'name' => $short->user->name,
                        'username' => $short->user->username,
                        'avatar_url' => $short->user->avatar_url,
                    ],
                    'user_reaction' => auth()->check() ? ($short->reactions->first()?->reaction ?? '') : '',
                ];
            });

        return response()->json([
            'shorts' => $shorts,
            'has_more' => $shorts->count() === 10
        ]);
    }
}
