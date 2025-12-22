<x-main-layout>
    <x-slot name="title">{{ $channel->name }} - {{ config('app.name') }}</x-slot>

    <!-- Channel Banner -->
    <div class="relative h-32 sm:h-48 bg-gradient-to-r from-gray-800 to-gray-900 rounded-xl overflow-hidden mb-6">
        @if($channel->cover_path)
            <img src="{{ $channel->cover_url }}" alt="{{ $channel->name }}" class="w-full h-full object-cover">
        @endif
    </div>

    <!-- Channel Info -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-6 mb-6">
        <!-- Avatar -->
        @if($channel->avatar_path)
            <img src="{{ $channel->avatar_url }}" alt="{{ $channel->name }}" class="w-20 h-20 sm:w-32 sm:h-32 rounded-full object-cover">
        @else
            <div class="w-20 h-20 sm:w-32 sm:h-32 rounded-full bg-red-600 flex items-center justify-center text-white text-3xl sm:text-5xl font-bold">
                {{ strtoupper(substr($channel->name, 0, 1)) }}
            </div>
        @endif

        <!-- Info -->
        <div class="flex-1">
            <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ $channel->name }}</h1>
            <p class="text-gray-400">@{{ $channel->username }}</p>
            <div class="flex items-center space-x-4 text-sm text-gray-400 mt-2">
                <span>{{ number_format($subscribersCount) }} subscribers</span>
                <span>{{ number_format($channel->videos()->count()) }} videos</span>
            </div>
            @if($channel->bio)
                <p class="text-gray-400 mt-2 max-w-2xl">{{ Str::limit($channel->bio, 150) }}</p>
            @endif
        </div>

        <!-- Subscribe Button -->
        @auth
            @if(auth()->id() !== $channel->id)
                <form action="{{ route('channel.subscribe', $channel->username) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-2 rounded-full font-medium {{ $isSubscribed ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-900 hover:bg-gray-200' }}">
                        {{ $isSubscribed ? 'Subscribed' : 'Subscribe' }}
                    </button>
                </form>
            @else
                <a href="{{ route('studio.dashboard') }}" class="px-6 py-2 bg-gray-700 text-white rounded-full font-medium hover:bg-gray-600">
                    Manage Channel
                </a>
            @endif
        @else
            <a href="{{ route('login') }}" class="px-6 py-2 bg-white text-gray-900 rounded-full font-medium hover:bg-gray-200">
                Subscribe
            </a>
        @endauth
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-800 mb-6">
        <nav class="flex space-x-8">
            <a href="{{ route('channel.show', $channel->username) }}" 
               class="py-4 text-sm font-medium border-b-2 {{ request()->routeIs('channel.show') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                Home
            </a>
            <a href="{{ route('channel.videos', $channel->username) }}" 
               class="py-4 text-sm font-medium border-b-2 {{ request()->routeIs('channel.videos') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                Videos
            </a>
            <a href="{{ route('channel.shorts', $channel->username) }}" 
               class="py-4 text-sm font-medium border-b-2 {{ request()->routeIs('channel.shorts') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                Shorts
            </a>
            <a href="{{ route('channel.playlists', $channel->username) }}" 
               class="py-4 text-sm font-medium border-b-2 {{ request()->routeIs('channel.playlists') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                Playlists
            </a>
            <a href="{{ route('channel.about', $channel->username) }}" 
               class="py-4 text-sm font-medium border-b-2 {{ request()->routeIs('channel.about') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                About
            </a>
        </nav>
    </div>

    <!-- Videos Grid -->
    @if($videos->count() > 0)
        <div class="mb-8">
            <h2 class="text-lg font-bold text-white mb-4">Videos</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($videos as $video)
                    @include('components.video-card', ['video' => $video])
                @endforeach
            </div>

            @if($videos->hasPages())
                <div class="mt-8">
                    {{ $videos->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-400 text-lg">No videos yet</p>
        </div>
    @endif

    <!-- Playlists Section -->
    @if($playlists->count() > 0)
        <div class="mt-8">
            <h2 class="text-lg font-bold text-white mb-4">Playlists</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($playlists as $playlist)
                    <a href="{{ route('playlists.show', $playlist) }}" class="group">
                        <div class="relative aspect-video bg-gray-800 rounded-xl overflow-hidden mb-3">
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </div>
                            <div class="absolute bottom-0 right-0 bg-black/80 px-2 py-1 text-xs text-white">
                                {{ $playlist->videos_count }} videos
                            </div>
                        </div>
                        <h3 class="text-sm font-medium text-white group-hover:text-gray-300 line-clamp-2">{{ $playlist->name }}</h3>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</x-main-layout>
