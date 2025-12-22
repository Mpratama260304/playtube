<x-main-layout>
    <x-slot name="title">Library - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8">Library</h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Watch History -->
            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        History
                    </h2>
                    <a href="{{ route('library.history') }}" class="text-sm text-blue-400 hover:text-blue-300">See All</a>
                </div>
                @if($history->count() > 0)
                    <div class="space-y-3">
                        @foreach($history->take(4) as $item)
                            @if($item->video)
                                <a href="{{ route('video.watch', $item->video->slug) }}" class="flex space-x-3 group">
                                    <div class="w-32 aspect-video bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                        @if($item->video->thumbnail)
                                            <img src="{{ $item->video->thumbnail_url }}" alt="{{ $item->video->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-white line-clamp-2 group-hover:text-gray-300">{{ $item->video->title }}</h4>
                                        <p class="text-xs text-gray-400 mt-1">{{ $item->video->user->name }}</p>
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400 text-center py-4">No watch history</p>
                @endif
            </div>

            <!-- Watch Later -->
            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Watch Later
                    </h2>
                    <a href="{{ route('library.watch-later') }}" class="text-sm text-blue-400 hover:text-blue-300">See All</a>
                </div>
                @if($watchLater->count() > 0)
                    <div class="space-y-3">
                        @foreach($watchLater->take(4) as $item)
                            @if($item->video)
                                <a href="{{ route('video.watch', $item->video->slug) }}" class="flex space-x-3 group">
                                    <div class="w-32 aspect-video bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                        @if($item->video->thumbnail)
                                            <img src="{{ $item->video->thumbnail_url }}" alt="{{ $item->video->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-white line-clamp-2 group-hover:text-gray-300">{{ $item->video->title }}</h4>
                                        <p class="text-xs text-gray-400 mt-1">{{ $item->video->user->name }}</p>
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400 text-center py-4">No videos saved</p>
                @endif
            </div>

            <!-- Liked Videos -->
            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                        Liked Videos
                    </h2>
                    <a href="{{ route('library.liked') }}" class="text-sm text-blue-400 hover:text-blue-300">See All</a>
                </div>
                @if($likedVideos->count() > 0)
                    <div class="space-y-3">
                        @foreach($likedVideos->take(4) as $reaction)
                            @if($reaction->video)
                                <a href="{{ route('video.watch', $reaction->video->slug) }}" class="flex space-x-3 group">
                                    <div class="w-32 aspect-video bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                        @if($reaction->video->thumbnail)
                                            <img src="{{ $reaction->video->thumbnail_url }}" alt="{{ $reaction->video->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-white line-clamp-2 group-hover:text-gray-300">{{ $reaction->video->title }}</h4>
                                        <p class="text-xs text-gray-400 mt-1">{{ $reaction->video->user->name }}</p>
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400 text-center py-4">No liked videos</p>
                @endif
            </div>

            <!-- Playlists -->
            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Playlists
                    </h2>
                    <a href="{{ route('playlists.index') }}" class="text-sm text-blue-400 hover:text-blue-300">See All</a>
                </div>
                @if($playlists->count() > 0)
                    <div class="space-y-3">
                        @foreach($playlists->take(4) as $playlist)
                            <a href="{{ route('playlist.show', $playlist->slug) }}" class="flex space-x-3 group">
                                <div class="w-32 aspect-video bg-gray-700 rounded-lg overflow-hidden flex-shrink-0 relative">
                                    @if($playlist->videos->first() && $playlist->videos->first()->thumbnail)
                                        <img src="{{ $playlist->videos->first()->thumbnail_url }}" alt="{{ $playlist->name }}" class="w-full h-full object-cover">
                                    @endif
                                    <div class="absolute bottom-1 right-1 bg-black/80 px-1 py-0.5 text-xs rounded">
                                        {{ $playlist->videos_count ?? $playlist->videos->count() }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-white line-clamp-2 group-hover:text-gray-300">{{ $playlist->name }}</h4>
                                    <p class="text-xs text-gray-400 mt-1">{{ ucfirst($playlist->visibility) }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400 text-center py-4">No playlists</p>
                @endif
            </div>
        </div>
    </div>
</x-main-layout>
