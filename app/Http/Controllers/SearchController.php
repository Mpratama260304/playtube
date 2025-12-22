<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Video;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q', '');
        $categorySlug = $request->input('category');
        $sort = $request->input('sort', 'relevance');
        $duration = $request->input('duration');

        $videos = Video::published()
            ->with(['user', 'category'])
            ->where('is_short', false);

        // Search query
        if ($query) {
            $videos->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('tags', function ($t) use ($query) {
                        $t->where('name', 'like', "%{$query}%");
                    });
            });
        }

        // Category filter
        if ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $videos->where('category_id', $category->id);
            }
        }

        // Duration filter
        if ($duration) {
            switch ($duration) {
                case 'short':
                    $videos->where('duration_seconds', '<', 240); // Under 4 min
                    break;
                case 'medium':
                    $videos->whereBetween('duration_seconds', [240, 1200]); // 4-20 min
                    break;
                case 'long':
                    $videos->where('duration_seconds', '>', 1200); // Over 20 min
                    break;
            }
        }

        // Sorting
        switch ($sort) {
            case 'latest':
                $videos->orderByDesc('published_at');
                break;
            case 'views':
                $videos->orderByDesc('views_count');
                break;
            case 'relevance':
            default:
                if ($query) {
                    // Basic relevance: exact title match first, then by views
                    $videos->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END", ["%{$query}%"])
                        ->orderByDesc('views_count');
                } else {
                    $videos->orderByDesc('published_at');
                }
                break;
        }

        $videos = $videos->paginate(24)->withQueryString();
        $categories = Category::all();

        return view('search.index', compact('videos', 'query', 'categories', 'categorySlug', 'sort', 'duration'));
    }
}
