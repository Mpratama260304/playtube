<x-main-layout>
    <x-slot name="title">{{ $channel->name }} - Shorts - {{ config('app.name') }}</x-slot>

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
            <a href="{{ route('channel.shorts', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-white text-white">Shorts</a>
            <a href="{{ route('channel.playlists', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Playlists</a>
            <a href="{{ route('channel.about', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">About</a>
        </nav>
    </div>

    <!-- Shorts Grid -->
    @if($shorts->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            @foreach($shorts as $short)
                <a href="{{ route('shorts.watch', $short) }}" class="group">
                    <div class="relative aspect-[9/16] bg-gray-800 rounded-xl overflow-hidden">
                        @if($short->thumbnail)
                            <img src="{{ $short->thumbnail_url }}" alt="{{ $short->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/80 to-transparent">
                            <p class="text-white text-sm line-clamp-2">{{ $short->title }}</p>
                            <p class="text-gray-300 text-xs">{{ number_format($short->views_count) }} views</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $shorts->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <h3 class="text-lg font-medium text-white mb-2">No Shorts yet</h3>
            <p class="text-gray-400">This channel hasn't uploaded any Shorts.</p>
        </div>
    @endif
</x-main-layout>
