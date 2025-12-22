<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Video;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function show(string $username)
    {
        $channel = User::where('username', $username)->firstOrFail();

        $videos = $channel->videos()
            ->published()
            ->where('is_short', false)
            ->latest('published_at')
            ->paginate(12);

        $playlists = $channel->playlists()
            ->public()
            ->withCount('videos')
            ->latest()
            ->take(6)
            ->get();

        $isSubscribed = auth()->check() && auth()->user()->isSubscribedTo($channel);
        $subscribersCount = $channel->subscribers_count;

        return view('channel.show', compact('channel', 'videos', 'playlists', 'isSubscribed', 'subscribersCount'));
    }

    public function videos(string $username)
    {
        $channel = User::where('username', $username)->firstOrFail();

        $videos = $channel->videos()
            ->published()
            ->where('is_short', false)
            ->latest('published_at')
            ->paginate(24);

        return view('channel.videos', compact('channel', 'videos'));
    }

    public function playlists(string $username)
    {
        $channel = User::where('username', $username)->firstOrFail();

        $playlists = $channel->playlists()
            ->public()
            ->withCount('videos')
            ->with(['videos' => fn($q) => $q->limit(1)])
            ->latest()
            ->paginate(12);

        return view('channel.playlists', compact('channel', 'playlists'));
    }

    public function shorts(string $username)
    {
        $channel = User::where('username', $username)->firstOrFail();

        $shorts = $channel->videos()
            ->published()
            ->where('is_short', true)
            ->latest('published_at')
            ->paginate(24);

        return view('channel.shorts', compact('channel', 'shorts'));
    }

    public function about(string $username)
    {
        $channel = User::where('username', $username)->firstOrFail();

        $videosCount = $channel->videos()->published()->count();
        $totalViews = (int) $channel->videos()->sum('views_count');

        return view('channel.about', compact('channel', 'videosCount', 'totalViews'));
    }

    public function subscribe(string $username, NotificationService $notificationService)
    {
        $channel = User::where('username', $username)->firstOrFail();

        if (auth()->id() === $channel->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot subscribe to your own channel.',
            ], 400);
        }

        $user = auth()->user();

        if ($user->isSubscribedTo($channel)) {
            $user->subscriptions()->detach($channel->id);
            $subscribed = false;
        } else {
            $user->subscriptions()->attach($channel->id);
            $notificationService->notifyNewSubscriber($channel, $user);
            $subscribed = true;
        }

        return response()->json([
            'success' => true,
            'subscribed' => $subscribed,
            'subscribers_count' => $channel->subscribers()->count(),
        ]);
    }
}
