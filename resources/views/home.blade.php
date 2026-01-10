<x-main-layout>
    <x-slot name="title">{{ config('app.name', 'PlayTube') }} - Home</x-slot>

    <div class="w-full max-w-[2000px] mx-auto overflow-hidden">
        <!-- Category Pills - Full bleed horizontal scroll on mobile -->
        <div class="mb-4 md:mb-6">
            <div class="flex gap-2 sm:gap-3 overflow-x-auto scrollbar-hide pb-2 -mx-3 px-3 sm:mx-0 sm:px-0">
                <a href="{{ route('home') }}" 
                   class="flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-lg transition-colors whitespace-nowrap
                          {{ !request('category') 
                             ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' 
                             : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                >
                    All
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('home', ['category' => $category->slug]) }}" 
                       class="flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-lg whitespace-nowrap transition-colors
                              {{ request('category') === $category->slug 
                                 ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' 
                                 : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Videos -->
        @if($videos->count() > 0)
            {{-- Mobile: Compact row list (YouTube mobile style) --}}
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
        @else
            <x-ui.empty-state 
                title="No videos found"
                description="Be the first to upload a video!"
                action="Upload Video"
                :actionHref="auth()->check() ? route('studio.upload') : route('login')"
            >
                <x-slot name="icon">
                    <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </x-slot>
            </x-ui.empty-state>
        @endif

        <!-- Pagination -->
        @if($videos->hasPages())
            <div class="mt-6 md:mt-8 flex justify-center">
                {{ $videos->links() }}
            </div>
        @endif
    </div>
</x-main-layout>
