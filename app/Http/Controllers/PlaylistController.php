<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaylistRequest;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    public function index()
    {
        $playlists = auth()->user()->playlists()
            ->withCount('videos')
            ->latest()
            ->paginate(12);

        return view('playlists.index', compact('playlists'));
    }

    public function create()
    {
        return view('playlists.create');
    }

    public function store(StorePlaylistRequest $request)
    {
        $playlist = auth()->user()->playlists()->create($request->validated());

        return redirect()
            ->route('playlists.show', $playlist)
            ->with('success', 'Playlist created successfully.');
    }

    public function show(Playlist $playlist)
    {
        $this->authorize('view', $playlist);

        $videos = $playlist->videos()
            ->with(['user'])
            ->paginate(24);

        return view('playlists.show', compact('playlist', 'videos'));
    }

    public function edit(Playlist $playlist)
    {
        $this->authorize('update', $playlist);

        return view('playlists.edit', compact('playlist'));
    }

    public function update(StorePlaylistRequest $request, Playlist $playlist)
    {
        $this->authorize('update', $playlist);

        $playlist->update($request->validated());

        return redirect()
            ->route('playlists.show', $playlist)
            ->with('success', 'Playlist updated successfully.');
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('delete', $playlist);

        $playlist->delete();

        return redirect()
            ->route('playlists.index')
            ->with('success', 'Playlist deleted successfully.');
    }

    public function addVideo(Request $request, Playlist $playlist)
    {
        $this->authorize('update', $playlist);

        $request->validate([
            'video_id' => ['required', 'exists:videos,id'],
        ]);

        $video = Video::findOrFail($request->video_id);
        $playlist->addVideo($video);

        return response()->json([
            'success' => true,
            'message' => 'Video added to playlist.',
        ]);
    }

    public function removeVideo(Playlist $playlist, Video $video)
    {
        $this->authorize('update', $playlist);

        $playlist->removeVideo($video);

        return response()->json([
            'success' => true,
            'message' => 'Video removed from playlist.',
        ]);
    }
}
