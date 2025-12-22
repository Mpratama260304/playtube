<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\Video;
use Illuminate\Http\Request;

class VideoApiController extends Controller
{
    public function index(Request $request)
    {
        $videos = Video::published()
            ->with(['user:id,name,username,avatar_path', 'category:id,name,slug'])
            ->where('is_short', false);

        if ($request->has('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $videos->where('category_id', $category->id);
            }
        }

        if ($request->has('trending')) {
            $videos->trending();
        } else {
            $videos->latest('published_at');
        }

        return response()->json([
            'data' => $videos->paginate($request->per_page ?? 20),
        ]);
    }

    public function show(Video $video)
    {
        if (!$video->isPublished() && (!auth()->check() || auth()->id() !== $video->user_id)) {
            return response()->json(['error' => 'Video not found'], 404);
        }

        $video->load(['user:id,name,username,avatar_path,bio', 'category:id,name,slug', 'tags:id,name']);

        return response()->json([
            'data' => $video,
        ]);
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

    public function comments(Request $request, Video $video)
    {
        $comments = $video->comments()
            ->with(['user:id,name,username,avatar_path', 'replies.user:id,name,username,avatar_path'])
            ->rootComments()
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $comments,
        ]);
    }

    public function storeComment(StoreCommentRequest $request, Video $video)
    {
        $comment = Comment::create([
            'video_id' => $video->id,
            'user_id' => auth()->id(),
            'parent_id' => $request->parent_id,
            'body' => $request->body,
        ]);

        $video->increment('comments_count');

        $comment->load('user:id,name,username,avatar_path');

        return response()->json([
            'success' => true,
            'data' => $comment,
        ]);
    }
}
