<x-main-layout>
    <x-slot name="title">{{ $channel->name }} - Playlists - {{ config('app.name') }}</x-slot>

    <!-- Channel Header Mini -->
    <div class="flex items-center space-x-4 mb-6 p-4 bg-gray-800 rounded-xl">
        @if($channel->avatar_path)
            <img src="{{ $channel->avatar_url }}" alt="{{ $channel->name }}" class="w-16 h-16 rounded-full object-cover">
        @else
            <div class="w-16 h-16 rounded-full bg-red-600 flex items-center justify-center text-white text-2xl font-bold">
                {{ strtoupper(substr($channel->name, 0, 1)) }}
            </div>
        @endif
        <div class="flex-1">
            <h1 class="text-xl font-bold text-white">{{ $channel->name }}</h1>
            <p class="text-gray-400 text-sm">@{{ $channel->username }} • {{ number_format($channel->subscribers_count) }} subscribers</p>
        </div>
        <a href="{{ route('channel.show', $channel->username) }}" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600">
            View Channel
        </a>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-800 mb-6">
        <nav class="flex space-x-8">
            <a href="{{ route('channel.show', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Home</a>
            <a href="{{ route('channel.videos', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Videos</a>
            <a href="{{ route('channel.shorts', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Shorts</a>
            <a href="{{ route('channel.playlists', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-white text-white">Playlists</a>
            <a href="{{ route('channel.about', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">About</a>
        </nav>
    </div>

    <!-- Playlists Grid -->
    @if($playlists->count() > 0)
        <div class="pt-video-grid">
            @foreach($playlists as $playlist)
                <a href="{{ route('playlists.show', $playlist) }}" class="group">
                    <div class="relative aspect-video bg-gray-800 rounded-xl overflow-hidden">
                        @if($playlist->videos->first()?->thumbnail)
                            <img src="{{ $playlist->videos->first()->thumbnail_url }}" alt="{{ $playlist->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-700 to-gray-800">
                                <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                        <div class="absolute bottom-0 right-0 bg-black/80 text-white text-xs px-2 py-1 m-2 rounded flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            <span>{{ $playlist->videos_count }} videos</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h3 class="font-medium text-white line-clamp-2 group-hover:text-gray-300">{{ $playlist->name }}</h3>
                        <p class="text-sm text-gray-400 mt-1">View full playlist</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $playlists->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            <h3 class="text-lg font-medium text-white mb-2">No playlists yet</h3>
            <p class="text-gray-400">This channel hasn't created any public playlists.</p>
        </div>
    @endif
</x-main-layout>
