<x-main-layout>
    <x-slot name="title">{{ $channel->name }} - {{ config('app.name') }}</x-slot>

    <!-- Channel Banner - Shorter on mobile -->
    <div class="relative h-24 sm:h-32 md:h-48 bg-gradient-to-r from-gray-800 to-gray-900 rounded-lg sm:rounded-xl overflow-hidden mb-4 sm:mb-6 -mx-2 sm:mx-0">
        @if($channel->cover_path)
            <img src="{{ $channel->cover_url }}" alt="{{ $channel->name }}" class="w-full h-full object-cover">
        @endif
    </div>

    <!-- Channel Info - Stack on mobile -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-6 mb-4 sm:mb-6 px-1 sm:px-0">
        <!-- Avatar - Smaller on mobile -->
        @if($channel->avatar_path)
            <img src="{{ $channel->avatar_url }}" alt="{{ $channel->name }}" class="w-16 h-16 sm:w-20 sm:h-20 md:w-32 md:h-32 rounded-full object-cover flex-shrink-0">
        @else
            <div class="w-16 h-16 sm:w-20 sm:h-20 md:w-32 md:h-32 rounded-full bg-red-600 flex items-center justify-center text-white text-2xl sm:text-3xl md:text-5xl font-bold flex-shrink-0">
                {{ strtoupper(substr($channel->name, 0, 1)) }}
            </div>
        @endif

        <!-- Info -->
        <div class="flex-1 min-w-0">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-white truncate">{{ $channel->name }}</h1>
            <p class="text-gray-400 text-sm sm:text-base">{{ '@' . $channel->username }}</p>
            <div class="flex items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-400 mt-1 sm:mt-2 flex-wrap">
                <span>{{ number_format($subscribersCount) }} subscribers</span>
                <span class="hidden sm:inline">•</span>
                <span>{{ number_format($channel->videos()->count()) }} videos</span>
            </div>
            @if($channel->bio)
                <p class="text-gray-400 mt-2 text-sm line-clamp-2 sm:line-clamp-none sm:max-w-2xl">{{ Str::limit($channel->bio, 150) }}</p>
            @endif
        </div>

        <!-- Subscribe Button -->
        @auth
            @if(auth()->id() !== $channel->id)
                <form action="{{ route('channel.subscribe', $channel->username) }}" method="POST" class="flex-shrink-0 w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto px-4 sm:px-6 py-2 rounded-full text-sm font-medium min-h-[44px] {{ $isSubscribed ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-900 hover:bg-gray-200' }}">
                        {{ $isSubscribed ? 'Subscribed' : 'Subscribe' }}
                    </button>
                </form>
            @else
                <a href="{{ route('studio.dashboard') }}" class="flex-shrink-0 w-full sm:w-auto text-center px-4 sm:px-6 py-2 bg-gray-700 text-white rounded-full text-sm font-medium hover:bg-gray-600 min-h-[44px] flex items-center justify-center">
                    Manage Channel
                </a>
            @endif
        @else
            <a href="{{ route('login') }}" class="flex-shrink-0 w-full sm:w-auto text-center px-4 sm:px-6 py-2 bg-white text-gray-900 rounded-full text-sm font-medium hover:bg-gray-200 min-h-[44px] flex items-center justify-center">
                Subscribe
            </a>
        @endauth
    </div>

    <!-- Tabs - Horizontally scrollable on mobile -->
    <div class="border-b border-gray-800 mb-4 sm:mb-6 -mx-2 sm:mx-0">
        <nav class="flex gap-4 sm:gap-8 overflow-x-auto scrollbar-hide px-2 sm:px-0">
            <a href="{{ route('channel.show', $channel->username) }}" 
               class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 whitespace-nowrap flex-shrink-0 {{ request()->routeIs('channel.show') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                Home
            </a>
            <a href="{{ route('channel.videos', $channel->username) }}" 
               class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 whitespace-nowrap flex-shrink-0 {{ request()->routeIs('channel.videos') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                Videos
            </a>
            <a href="{{ route('channel.shorts', $channel->username) }}" 
               class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 whitespace-nowrap flex-shrink-0 {{ request()->routeIs('channel.shorts') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                Shorts
            </a>
            <a href="{{ route('channel.playlists', $channel->username) }}" 
               class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 whitespace-nowrap flex-shrink-0 {{ request()->routeIs('channel.playlists') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                Playlists
            </a>
            <a href="{{ route('channel.about', $channel->username) }}" 
               class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 whitespace-nowrap flex-shrink-0 {{ request()->routeIs('channel.about') ? 'border-white text-white' : 'border-transparent text-gray-400 hover:text-white' }}">
                About
            </a>
        </nav>
    </div>

    <!-- Videos Grid -->
    @if($videos->count() > 0)
        <div class="mb-8">
            <h2 class="text-lg font-bold text-white mb-4">Videos</h2>
            
            {{-- Mobile: Compact row list --}}
            <div class="block sm:hidden space-y-1">
                @foreach($videos as $video)
                    <x-video-row :video="$video" />
                @endforeach
            </div>

            {{-- Tablet & Desktop: Grid cards --}}
            <div class="hidden sm:grid pt-video-grid">
                @foreach($videos as $video)
                    <x-video-card :video="$video" />
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
            <div class="pt-video-grid">
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
