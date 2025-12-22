<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Video;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::published()
            ->with(['user', 'category'])
            ->withCount(['views', 'reactions as likes_count' => function ($q) {
                $q->where('type', 'like');
            }])
            ->where('is_short', false);

        // Filter by category if provided
        if ($request->filled('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        $videos = $query->latest()->paginate(24);
        
        $categories = Category::all();

        return view('home', compact('videos', 'categories'));
    }

    public function trending()
    {
        $videos = Video::published()
            ->with(['user', 'category'])
            ->withCount(['views', 'reactions as likes_count' => function ($q) {
                $q->where('type', 'like');
            }])
            ->where('is_short', false)
            ->trending(7)
            ->paginate(24);
        
        $categories = Category::all();

        return view('home', compact('videos', 'categories'));
    }

    public function latest()
    {
        $videos = Video::published()
            ->with(['user', 'category'])
            ->withCount(['views', 'reactions as likes_count' => function ($q) {
                $q->where('type', 'like');
            }])
            ->where('is_short', false)
            ->latest()
            ->paginate(24);
        
        $categories = Category::all();

        return view('home', compact('videos', 'categories'));
    }
}
