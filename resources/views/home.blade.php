<x-main-layout>
    <x-slot name="title">{{ config('app.name', 'PlayTube') }} - Home</x-slot>

    <!-- Category Pills -->
    <div class="mb-6 overflow-x-auto scrollbar-hide">
        <div class="flex space-x-2 pb-2">
            <a href="{{ route('home') }}" 
               class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap {{ !request('category') ? 'bg-white text-gray-900' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                All
            </a>
            @foreach($categories as $category)
                <a href="{{ route('home', ['category' => $category->slug]) }}" 
                   class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap {{ request('category') === $category->slug ? 'bg-white text-gray-900' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Videos Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($videos as $video)
            @include('components.video-card', ['video' => $video])
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-lg">No videos found</p>
                <p class="text-gray-500 text-sm mt-2">Be the first to upload a video!</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $videos->links() }}
    </div>
</x-main-layout>
