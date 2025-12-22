<x-main-layout>
    <x-slot name="title">{{ $category->name }} - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Category Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white mb-2">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-gray-400">{{ $category->description }}</p>
            @endif
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
                    <p class="text-gray-400 text-lg">No videos in this category</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $videos->links() }}
        </div>
    </div>
</x-main-layout>
