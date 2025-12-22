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
}
