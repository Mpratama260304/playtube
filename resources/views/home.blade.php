<x-main-layout>
    <x-slot name="title">{{ config('app.name', 'PlayTube') }} - Home</x-slot>

    <div class="w-full max-w-[2000px] mx-auto">
        <!-- Category Pills - Full bleed horizontal scroll on mobile -->
        <div class="mb-4 sm:mb-6 -mx-2 sm:-mx-4 lg:-mx-6">
            <div class="flex gap-2 sm:gap-3 overflow-x-auto scrollbar-hide px-2 sm:px-4 lg:px-6 pb-2">
                <a href="{{ route('home') }}" 
                   class="flex-shrink-0 px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium rounded-lg transition-colors whitespace-nowrap
                          {{ !request('category') 
                             ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' 
                             : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                >
                    All
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('home', ['category' => $category->slug]) }}" 
                       class="flex-shrink-0 px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium rounded-lg whitespace-nowrap transition-colors
                              {{ request('category') === $category->slug 
                                 ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' 
                                 : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Videos Grid - Mobile-first: 1 col, then responsive -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 sm:gap-x-4 sm:gap-y-8">
            @forelse($videos as $video)
                <x-video-card :video="$video" />
            @empty
                <div class="col-span-full">
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
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($videos->hasPages())
            <div class="mt-8 flex justify-center">
                {{ $videos->links() }}
            </div>
        @endif
    </div>
</x-main-layout>
