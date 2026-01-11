<x-main-layout>
    <x-slot name="title">Shorts - {{ config('app.name') }}</x-slot>

    <div class="w-full" x-data="shortsGrid()">
        <!-- Header -->
        <div class="flex items-center justify-center gap-2 mb-4 sm:mb-6 md:mb-8">
            <div class="bg-red-600 rounded-lg p-1.5 sm:p-2">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.77 10.32l-1.2-.5L18 9.06c1.84-.96 2.53-3.23 1.56-5.06s-3.24-2.53-5.07-1.56L6 6.94c-1.29.68-2.07 2.04-2 3.49.07 1.42.93 2.67 2.22 3.25.03.01 1.2.5 1.2.5L6 14.93c-1.83.97-2.53 3.24-1.56 5.07.97 1.83 3.24 2.53 5.07 1.56l8.5-4.5c1.29-.68 2.06-2.04 1.99-3.49-.07-1.42-.94-2.68-2.23-3.25zM10 14.65v-5.3L15 12l-5 2.65z"/>
                </svg>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-white">Shorts</h1>
        </div>

        @if($shorts->count() > 0)
            <!-- Responsive Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 sm:gap-3 md:gap-4">
                @foreach($shorts as $short)
                    <a href="{{ route('shorts.show', $short->slug) }}" 
                       class="group relative block rounded-lg sm:rounded-xl overflow-hidden bg-gray-900 hover:ring-2 hover:ring-white/30 transition-all duration-200">
                        <!-- Video Container with 9:16 aspect ratio -->
                        <div class="relative aspect-[9/16] bg-black">
                            <!-- Thumbnail/Video -->
                            <video 
                                class="absolute inset-0 w-full h-full object-cover"
                                poster="{{ $short->thumbnail_url }}"
                                muted
                                loop
                                playsinline
                                preload="none"
                                x-ref="video{{ $short->id }}"
                            >
                                <source src="{{ $short->video_url }}" type="video/mp4">
                            </video>
                            
                            <!-- Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent pointer-events-none"></div>
                            
                            <!-- Play Icon on Hover (Desktop) -->
                            <div class="absolute inset-0 hidden sm:flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <div class="w-12 h-12 md:w-14 md:h-14 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 md:w-7 md:h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Duration Badge -->
                            @if($short->duration)
                                <div class="absolute top-2 right-2 bg-black/70 text-white text-[10px] sm:text-xs px-1.5 py-0.5 rounded font-medium">
                                    {{ gmdate($short->duration >= 3600 ? 'H:i:s' : 'i:s', $short->duration) }}
                                </div>
                            @endif

                            <!-- Bottom Info Overlay -->
                            <div class="absolute bottom-0 left-0 right-0 p-2 sm:p-3">
                                <!-- Title -->
                                <h3 class="text-white text-xs sm:text-sm font-medium line-clamp-2 leading-tight mb-1 sm:mb-2 drop-shadow-lg">
                                    {{ Str::limit($short->title, 50) }}
                                </h3>
                                
                                <!-- Channel & Stats -->
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <!-- Avatar -->
                                    @if($short->user->avatar)
                                        <img src="{{ $short->user->avatar_url }}" 
                                             alt="{{ $short->user->name }}" 
                                             class="w-5 h-5 sm:w-6 sm:h-6 rounded-full object-cover ring-1 ring-white/30 flex-shrink-0">
                                    @else
                                        <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-red-600 flex items-center justify-center text-white text-[10px] sm:text-xs font-medium ring-1 ring-white/30 flex-shrink-0">
                                            {{ strtoupper(substr($short->user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    
                                    <!-- Channel Name & Views -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-white/90 text-[10px] sm:text-xs font-medium truncate">{{ $short->user->name }}</p>
                                        <p class="text-white/60 text-[9px] sm:text-[10px] flex items-center gap-1">
                                            <span>{{ $short->views_formatted }} views</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($shorts->hasPages())
                <div class="mt-6 sm:mt-8 flex justify-center">
                    {{ $shorts->links() }}
                </div>
            @endif

            <!-- Load More Button (Alternative) -->
            @if($shorts->hasMorePages())
                <div class="mt-6 sm:mt-8 text-center">
                    <a href="{{ $shorts->nextPageUrl() }}" 
                       class="inline-flex items-center gap-2 px-6 py-2.5 bg-gray-800 hover:bg-gray-700 text-white text-sm font-medium rounded-full transition-colors">
                        <span>Load more</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-16 sm:py-24">
                <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gray-800 rounded-full flex items-center justify-center mb-4 sm:mb-6">
                    <svg class="w-10 h-10 sm:w-12 sm:h-12 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.77 10.32l-1.2-.5L18 9.06c1.84-.96 2.53-3.23 1.56-5.06s-3.24-2.53-5.07-1.56L6 6.94c-1.29.68-2.07 2.04-2 3.49.07 1.42.93 2.67 2.22 3.25.03.01 1.2.5 1.2.5L6 14.93c-1.83.97-2.53 3.24-1.56 5.07.97 1.83 3.24 2.53 5.07 1.56l8.5-4.5c1.29-.68 2.06-2.04 1.99-3.49-.07-1.42-.94-2.68-2.23-3.25zM10 14.65v-5.3L15 12l-5 2.65z"/>
                    </svg>
                </div>
                <h2 class="text-lg sm:text-xl font-semibold text-white mb-2">No Shorts yet</h2>
                <p class="text-gray-400 text-sm sm:text-base text-center max-w-md px-4">
                    Short videos will appear here. Check back later for new content!
                </p>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function shortsGrid() {
            return {
                init() {
                    // Add hover preview on desktop
                    if (window.matchMedia('(min-width: 640px)').matches) {
                        this.initHoverPreview();
                    }
                },
                initHoverPreview() {
                    document.querySelectorAll('[x-ref^="video"]').forEach(video => {
                        const parent = video.closest('a');
                        if (!parent) return;
                        
                        let playTimeout;
                        
                        parent.addEventListener('mouseenter', () => {
                            playTimeout = setTimeout(() => {
                                video.play().catch(() => {});
                            }, 200);
                        });
                        
                        parent.addEventListener('mouseleave', () => {
                            clearTimeout(playTimeout);
                            video.pause();
                            video.currentTime = 0;
                        });
                    });
                }
            }
        }
    </script>
    @endpush
</x-main-layout>
