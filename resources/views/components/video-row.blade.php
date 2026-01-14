@props(['video'])

{{-- Compact Video Row Component - YouTube Mobile Style --}}
{{-- Used for mobile lists: home, related, search, etc. --}}
<a href="{{ route('video.watch', $video->slug) }}" 
   class="flex gap-3 group hover:bg-gray-100 dark:hover:bg-gray-800/50 rounded-lg p-1.5 -mx-1.5 transition-colors"
>
    {{-- Thumbnail - Fixed width, 16:9 aspect ratio --}}
    <div class="relative w-40 sm:w-44 aspect-video bg-gradient-to-br from-gray-700 to-gray-900 rounded-lg overflow-hidden flex-shrink-0">
        @if($video->has_thumbnail)
            <img 
                src="{{ $video->thumbnail_url }}" 
                alt="{{ $video->title }}" 
                loading="lazy"
                decoding="async"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                @if($video->isEmbed() && $video->embed_platform === 'googledrive' && $video->embed_video_id)
                onerror="if(!this.dataset.tried){this.dataset.tried='1';this.src='https://drive.google.com/thumbnail?id={{ $video->embed_video_id }}&sz=w640';}else{this.style.display='none';}"
                @else
                onerror="this.style.display='none';"
                @endif
            >
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        {{-- Embed Badge --}}
        @if($video->isEmbed() && $video->embed_platform)
            <div class="absolute top-1 left-1 px-1 py-0.5 bg-black/70 text-white text-[8px] font-medium rounded">
                {{ $video->embed_platform_name }}
            </div>
        @endif

        {{-- Duration Badge --}}
        @if($video->duration)
            <div class="absolute bottom-1 right-1 px-1 py-0.5 bg-black/80 text-white text-[10px] font-medium rounded">
                {{ $video->formatted_duration }}
            </div>
        @endif
    </div>

    {{-- Video Info - Right side --}}
    <div class="flex-1 min-w-0 py-0.5">
        {{-- Title - 2 lines max --}}
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2 leading-snug group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">
            {{ $video->title }}
        </h3>
        
        {{-- Channel Name --}}
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">
            {{ $video->user->name }}
        </p>
        
        {{-- Views & Time --}}
        <p class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">
            {{ number_format($video->views_count ?? 0) }} views • {{ $video->created_at->diffForHumans(null, true) }}
        </p>
    </div>

    {{-- Optional: 3-dot menu for mobile --}}
    <div class="flex-shrink-0 self-start pt-1">
        <button type="button" 
                onclick="event.preventDefault(); event.stopPropagation();" 
                class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 opacity-0 group-hover:opacity-100 transition-opacity"
        >
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
        </button>
    </div>
</a>
