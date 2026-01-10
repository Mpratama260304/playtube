<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\Video;
use App\Models\View;
use App\Models\WatchHistory;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function watch(Video $video, NotificationService $notificationService)
    {
        $user = auth()->user();

        // Use the model's canBeViewedBy method for access control
        if (!$video->canBeViewedBy($user)) {
            abort(404);
        }

        // Check if this is a preview (processing video being viewed by owner/admin)
        $isPreview = $video->status !== Video::STATUS_PUBLISHED;
        $isOwner = $user && $user->id === $video->user_id;
        $isAdmin = $user && $user->isAdmin();

        // Record view
        $this->recordView($video);

        // Record watch history if logged in
        if (auth()->check()) {
            WatchHistory::record(auth()->id(), $video->id);
        }

        // Get comments
        $comments = $video->comments()
            ->with(['user', 'replies.user'])
            ->rootComments()
            ->latest()
            ->paginate(15);

        // Get related videos - optimized with eager loading and cache
        $relatedVideos = cache()->remember(
            "related_videos_{$video->id}",
            now()->addMinutes(5),
            function () use ($video) {
                return Video::published()
                    ->where('id', '!=', $video->id)
                    ->where(function ($query) use ($video) {
                        $query->where('category_id', $video->category_id)
                            ->orWhere('user_id', $video->user_id);
                    })
                    ->select(['id', 'uuid', 'slug', 'title', 'user_id', 'thumbnail_path', 'duration', 'created_at', 'views_count'])
                    ->with(['user:id,name,username'])
                    ->inRandomOrder()
                    ->take(8)
                    ->get();
            }
        );

        // Get user's reaction if logged in
        $userReaction = null;
        $inWatchLater = false;
        $userPlaylists = [];
        $isSubscribed = false;
        
        if (auth()->check()) {
            $userReaction = Reaction::where([
                'user_id' => auth()->id(),
                'target_type' => 'video',
                'target_id' => $video->id,
            ])->first();

            $inWatchLater = auth()->user()->watchLater()->where('video_id', $video->id)->exists();
            $userPlaylists = auth()->user()->playlists()->get();
            $isSubscribed = auth()->user()->subscriptions()->where('channel_id', $video->user_id)->exists();
        }

        return view('video.watch', compact(
            'video',
            'comments',
            'relatedVideos',
            'userReaction',
            'inWatchLater',
            'userPlaylists',
            'isPreview',
            'isOwner',
            'isAdmin',
            'isSubscribed'
        ));
    }

    protected function recordView(Video $video): void
    {
        $sessionId = session()->getId();
        $userId = auth()->id();

        // Prevent duplicate views within 30 minutes
        $recentView = View::where('video_id', $video->id)
            ->where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if (!$recentView) {
            View::create([
                'video_id' => $video->id,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $video->incrementViews();
        }
    }

    public function react(Request $request, Video $video)
    {
        $request->validate([
            'reaction' => ['required', 'in:like,dislike'],
        ]);

        $result = Reaction::toggle(
            auth()->id(),
            'video',
            $video->id,
            $request->reaction
        );

        // Update counts
        $video->update([
            'likes_count' => Reaction::where(['target_type' => 'video', 'target_id' => $video->id, 'reaction' => 'like'])->count(),
            'dislikes_count' => Reaction::where(['target_type' => 'video', 'target_id' => $video->id, 'reaction' => 'dislike'])->count(),
        ]);

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'reaction' => $result['reaction'],
            'likes_count' => $video->fresh()->likes_count,
            'dislikes_count' => $video->fresh()->dislikes_count,
        ]);
    }

    public function comment(StoreCommentRequest $request, Video $video, NotificationService $notificationService)
    {
        $comment = Comment::create([
            'video_id' => $video->id,
            'user_id' => auth()->id(),
            'parent_id' => $request->parent_id,
            'body' => $request->body,
        ]);

        $video->increment('comments_count');

        // Send notification
        if ($request->parent_id) {
            $parentComment = Comment::find($request->parent_id);
            if ($parentComment) {
                $notificationService->notifyCommentReply($parentComment, $comment);
            }
        } else {
            $notificationService->notifyNewComment($video, $comment);
        }

        $comment->load('user');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $comment,
            ]);
        }

        return back()->with('success', 'Comment posted successfully.');
    }

    public function toggleWatchLater(Video $video)
    {
        $user = auth()->user();

        if ($user->watchLater()->where('video_id', $video->id)->exists()) {
            $user->watchLater()->detach($video->id);
            $added = false;
        } else {
            $user->watchLater()->attach($video->id);
            $added = true;
        }

        return response()->json([
            'success' => true,
            'added' => $added,
            'message' => $added ? 'Added to Watch Later' : 'Removed from Watch Later',
        ]);
    }

    public function addToPlaylist(Request $request, Video $video)
    {
        $request->validate([
            'playlist_id' => ['required', 'exists:playlists,id'],
        ]);

        $playlist = auth()->user()->playlists()->findOrFail($request->playlist_id);

        if ($playlist->hasVideo($video)) {
            $playlist->removeVideo($video);
            $added = false;
        } else {
            $playlist->addVideo($video);
            $added = true;
        }

        return response()->json([
            'success' => true,
            'added' => $added,
            'message' => $added ? 'Added to playlist' : 'Removed from playlist',
        ]);
    }

    public function updateProgress(Request $request, Video $video)
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:0'],
        ]);

        if (auth()->check()) {
            WatchHistory::record(auth()->id(), $video->id, $request->position);
        }

        return response()->json(['success' => true]);
    }
}
