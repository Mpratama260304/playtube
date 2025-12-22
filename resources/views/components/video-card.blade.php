<!-- Video Card Component -->
<div class="group">
    <a href="{{ route('video.watch', $video->slug) }}" class="block">
        <!-- Thumbnail -->
        <div class="relative aspect-video bg-gray-800 rounded-xl overflow-hidden mb-3">
            @if($video->has_thumbnail)
                <img src="{{ $video->thumbnail_url }}" 
                     alt="{{ $video->title }}" 
                     loading="lazy"
                     decoding="async"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif

            <!-- Duration -->
            @if($video->duration)
                <div class="absolute bottom-2 right-2 px-1.5 py-0.5 bg-black/80 rounded text-xs font-medium">
                    {{ $video->formatted_duration }}
                </div>
            @endif

            <!-- Hover Play Icon -->
            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <div class="w-12 h-12 bg-black/50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            </div>
        </div>
    </a>

    <!-- Info -->
    <div class="flex space-x-3">
        <!-- Channel Avatar -->
        <a href="{{ route('channel.show', $video->user->username) }}" class="flex-shrink-0">
            @if($video->user->avatar)
                <img src="{{ $video->user->avatar_url }}" 
                     alt="{{ $video->user->name }}" 
                     class="w-9 h-9 rounded-full object-cover">
            @else
                <div class="w-9 h-9 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium">
                    {{ strtoupper(substr($video->user->name, 0, 1)) }}
                </div>
            @endif
        </a>

        <!-- Text -->
        <div class="flex-1 min-w-0">
            <a href="{{ route('video.watch', $video->slug) }}" class="block">
                <h3 class="text-sm font-medium text-white line-clamp-2 group-hover:text-gray-300">
                    {{ $video->title }}
                </h3>
            </a>
            <a href="{{ route('channel.show', $video->user->username) }}" class="text-sm text-gray-400 hover:text-gray-300 mt-1 block">
                {{ $video->user->name }}
            </a>
            <div class="flex items-center text-xs text-gray-500 mt-1">
                <span>{{ number_format($video->views_count ?? $video->views()->count()) }} views</span>
                <span class="mx-1">•</span>
                <span>{{ $video->created_at->diffForHumans() }}</span>
            </div>
        </div>

        <!-- More Menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="p-1 text-gray-400 hover:text-white rounded opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                </svg>
            </button>

            <div x-show="open" 
                 @click.away="open = false"
                 x-transition
                 class="absolute right-0 mt-1 w-48 bg-gray-800 rounded-lg shadow-lg border border-gray-700 py-1 z-10"
                 style="display: none;">
                @auth
                    <form action="{{ route('video.watch-later', $video) }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Save to Watch Later
                        </button>
                    </form>
                    <button type="button" @click="$dispatch('add-to-playlist', { videoId: {{ $video->id }} })" class="flex items-center w-full px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add to Playlist
                    </button>
                @endauth
                <a href="{{ route('channel.show', $video->user->username) }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Go to Channel
                </a>
            </div>
        </div>
    </div>
</div>
