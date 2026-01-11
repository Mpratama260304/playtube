<x-main-layout>
    <x-slot name="title">{{ $channel->name }} - Videos - {{ config('app.name') }}</x-slot>

    <!-- Channel Header Mini - Responsive -->
    <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-6 p-3 sm:p-4 bg-gray-800 rounded-lg sm:rounded-xl">
        @if($channel->avatar_path)
            <img src="{{ $channel->avatar_url }}" alt="{{ $channel->name }}" class="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover flex-shrink-0">
        @else
            <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-red-600 flex items-center justify-center text-white text-xl sm:text-2xl font-bold flex-shrink-0">
                {{ strtoupper(substr($channel->name, 0, 1)) }}
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <h1 class="text-base sm:text-xl font-bold text-white truncate">{{ $channel->name }}</h1>
            <p class="text-gray-400 text-xs sm:text-sm truncate">{{ '@' . $channel->username }} • {{ number_format($channel->subscribers_count) }} subscribers</p>
        </div>
        <a href="{{ route('channel.show', $channel->username) }}" class="hidden sm:block px-4 py-2 bg-gray-700 text-white text-sm rounded-lg hover:bg-gray-600 flex-shrink-0">
            View Channel
        </a>
    </div>

    <!-- Tabs - Horizontally scrollable on mobile -->
    <div class="border-b border-gray-800 mb-4 sm:mb-6 -mx-2 sm:mx-0">
        <nav class="flex gap-4 sm:gap-8 overflow-x-auto scrollbar-hide px-2 sm:px-0">
            <a href="{{ route('channel.show', $channel->username) }}" class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white whitespace-nowrap flex-shrink-0">Home</a>
            <a href="{{ route('channel.videos', $channel->username) }}" class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 border-white text-white whitespace-nowrap flex-shrink-0">Videos</a>
            <a href="{{ route('channel.shorts', $channel->username) }}" class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white whitespace-nowrap flex-shrink-0">Shorts</a>
            <a href="{{ route('channel.playlists', $channel->username) }}" class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white whitespace-nowrap flex-shrink-0">Playlists</a>
            <a href="{{ route('channel.about', $channel->username) }}" class="py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white whitespace-nowrap flex-shrink-0">About</a>
        </nav>
    </div>

    <!-- Videos - Responsive Grid -->
    @if($videos->count() > 0)
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

        <div class="mt-8">
            {{ $videos->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <h3 class="text-lg font-medium text-white mb-2">No videos yet</h3>
            <p class="text-gray-400">This channel hasn't uploaded any videos.</p>
        </div>
    @endif
</x-main-layout>
