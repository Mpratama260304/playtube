<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Video;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['videos' => function ($query) {
            $query->where('status', 'published');
        }])
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function show(Category $category)
    {
        $videos = Video::where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('published_at')
            ->with(['user'])
            ->where('category_id', $category->id)
            ->where('is_short', false)
            ->latest('published_at')
            ->paginate(24);

        return view('categories.show', compact('category', 'videos'));
    }
}
