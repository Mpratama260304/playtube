<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;

class SearchApiController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $videos = Video::published()
            ->with(['user:id,name,username,avatar_path'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->take(10)
            ->get(['id', 'slug', 'title', 'thumbnail_path', 'views_count', 'user_id', 'duration_seconds']);

        return response()->json([
            'data' => $videos,
        ]);
    }
}
