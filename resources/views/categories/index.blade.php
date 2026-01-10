<x-main-layout>
    <x-slot name="title">Categories - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8">Browse Categories</h1>

        <div class="pt-video-grid">
            @foreach($categories as $category)
                <a href="{{ route('category.show', $category->slug) }}" class="group block bg-gray-800 rounded-xl p-6 hover:bg-gray-750 transition-colors">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-red-600/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2m0 2v2m0-2h10m-10 0c-1.333 0-2 .667-2 2v12c0 1.333.667 2 2 2h10c1.333 0 2-.667 2-2V6c0-1.333-.667-2-2-2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-white group-hover:text-gray-300">{{ $category->name }}</h3>
                            <p class="text-sm text-gray-400">{{ number_format($category->videos_count ?? $category->videos()->count()) }} videos</p>
                        </div>
                    </div>
                    @if($category->description)
                        <p class="text-sm text-gray-500 mt-3 line-clamp-2">{{ $category->description }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</x-main-layout>
