@props(['video', 'compact' => false])

<!-- Video Card Component - Fully responsive -->
<div class="group w-full min-w-0 overflow-hidden">
    <a href="{{ route('video.watch', $video->slug) }}" class="block">
        <!-- Thumbnail - Full width, aspect-video -->
        <div class="relative w-full aspect-video bg-gray-200 dark:bg-gray-800 rounded-lg overflow-hidden mb-2">
            @if($video->has_thumbnail)
                <img 
                    src="{{ $video->thumbnail_url }}" 
                    alt="{{ $video->title }}" 
                    loading="lazy"
                    decoding="async"
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                >
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif

            <!-- Duration Badge -->
            @if($video->duration)
                <div class="absolute bottom-1 right-1 px-1 py-0.5 bg-black/80 text-white text-[10px] font-medium rounded">
                    {{ $video->formatted_duration }}
                </div>
            @endif

            <!-- Hover Play Icon - Hidden on touch devices -->
            <div class="absolute inset-0 hidden md:flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-black/20">
                <div class="w-12 h-12 bg-black/60 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            </div>
        </div>
    </a>

    <!-- Info Section -->
    <div class="flex gap-2 min-w-0">
        <!-- Channel Avatar -->
        @if(!$compact)
        <a href="{{ route('channel.show', $video->user->username) }}" class="flex-shrink-0">
            @if($video->user->avatar)
                <img 
                    src="{{ $video->user->avatar_url }}" 
                    alt="{{ $video->user->name }}" 
                    class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover"
                >
            @else
                <div class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-medium">
                    {{ strtoupper(substr($video->user->name, 0, 1)) }}
                </div>
            @endif
        </a>
        @endif

        <!-- Text Content -->
        <div class="flex-1 min-w-0">
            <a href="{{ route('video.watch', $video->slug) }}" class="block group/title" title="{{ $video->title }}">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 group-hover/title:text-gray-600 dark:group-hover/title:text-gray-300 transition-colors leading-snug">
                    {{ $video->title }}
                </h3>
            </a>
            
            <a href="{{ route('channel.show', $video->user->username) }}" class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 mt-1 block transition-colors truncate">
                {{ $video->user->name }}
            </a>
            
            <div class="flex items-center flex-wrap text-[11px] text-gray-500 dark:text-gray-500 mt-0.5">
                <span>{{ number_format($video->views_count ?? $video->views()->count()) }} views</span>
                <span class="mx-1">•</span>
                <span>{{ $video->created_at->diffForHumans() }}</span>
            </div>
        </div>

        <!-- More Menu -->
        <div class="relative flex-shrink-0" x-data="{ open: false }">
            <button 
                @click="open = !open" 
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full opacity-0 group-hover:opacity-100 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                aria-label="More options"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
            </button>

            <div 
                x-show="open" 
                x-cloak
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-1 w-52 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-20"
            >
                @auth
                    <button 
                        type="button"
                        x-data="{ loading: false, saved: false }"
                        @click="
                            if (loading) return;
                            loading = true;
                            fetch('{{ route('video.watch-later', $video) }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(r => r.json())
                            .then(data => {
                                saved = data.added;
                                loading = false;
                                open = false;
                            })
                            .catch(() => loading = false);
                        "
                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="loading ? 'Saving...' : (saved ? 'Saved!' : 'Save to Watch Later')"></span>
                    </button>
                    <button 
                        type="button" 
                        @click="$dispatch('add-to-playlist', { videoId: {{ $video->id }} })" 
                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add to Playlist
                    </button>
                @endauth
                
                <a href="{{ route('channel.show', $video->user->username) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Go to Channel
                </a>
                
                <button 
                    @click="
                        open = false;
                        $dispatch('open-share-modal', {
                            title: '{{ addslashes($video->title) }}',
                            url: '{{ route('video.watch', $video->slug) }}',
                            showTimestamp: false
                        });
                    "
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    Share
                </button>
            </div>
        </div>
    </div>
</div>
