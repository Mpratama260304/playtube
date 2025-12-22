<x-main-layout>
    <x-slot name="title">{{ $channel->name }} - Videos - {{ config('app.name') }}</x-slot>

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
            <a href="{{ route('channel.videos', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-white text-white">Videos</a>
            <a href="{{ route('channel.shorts', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Shorts</a>
            <a href="{{ route('channel.playlists', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Playlists</a>
            <a href="{{ route('channel.about', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">About</a>
        </nav>
    </div>

    <!-- Videos Grid -->
    @if($videos->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($videos as $video)
                <div class="group">
                    <a href="{{ route('video.watch', $video) }}" class="block">
                        <div class="relative aspect-video bg-gray-800 rounded-xl overflow-hidden">
                            @if($video->has_thumbnail)
                                <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            @if($video->duration)
                                <span class="absolute bottom-2 right-2 bg-black/80 text-white text-xs px-1.5 py-0.5 rounded">
                                    {{ $video->formatted_duration }}
                                </span>
                            @endif
                        </div>
                    </a>
                    <div class="mt-3">
                        <a href="{{ route('video.watch', $video) }}" class="block">
                            <h3 class="font-medium text-white line-clamp-2 group-hover:text-gray-300">{{ $video->title }}</h3>
                        </a>
                        <p class="text-sm text-gray-400 mt-1">
                            {{ number_format($video->views_count) }} views • {{ $video->published_at?->diffForHumans() ?? $video->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
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
