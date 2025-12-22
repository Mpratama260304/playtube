<x-main-layout>
    <x-slot name="title">Search: {{ $query }} - {{ config('app.name') }}</x-slot>

    <div class="max-w-5xl mx-auto">
        <!-- Search Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-white mb-2">Search results for "{{ $query }}"</h1>
            <p class="text-gray-400">{{ number_format($videos->total()) }} videos found</p>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="{{ route('search', ['q' => $query, 'sort' => 'relevance']) }}" 
               class="px-4 py-2 text-sm rounded-lg {{ request('sort', 'relevance') === 'relevance' ? 'bg-white text-gray-900' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                Relevance
            </a>
            <a href="{{ route('search', ['q' => $query, 'sort' => 'date']) }}" 
               class="px-4 py-2 text-sm rounded-lg {{ request('sort') === 'date' ? 'bg-white text-gray-900' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                Upload Date
            </a>
            <a href="{{ route('search', ['q' => $query, 'sort' => 'views']) }}" 
               class="px-4 py-2 text-sm rounded-lg {{ request('sort') === 'views' ? 'bg-white text-gray-900' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                View Count
            </a>
        </div>

        <!-- Results -->
        <div class="space-y-4">
            @forelse($videos as $video)
                <a href="{{ route('video.watch', $video->slug) }}" class="flex space-x-4 group">
                    <!-- Thumbnail -->
                    <div class="relative w-64 aspect-video bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                        @if($video->has_thumbnail)
                            <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        @if($video->duration)
                            <div class="absolute bottom-2 right-2 px-1.5 py-0.5 bg-black/80 rounded text-xs">
                                {{ $video->formatted_duration }}
                            </div>
                        @endif
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <h2 class="text-lg font-medium text-white group-hover:text-gray-300 line-clamp-2">{{ $video->title }}</h2>
                        <div class="flex items-center text-sm text-gray-400 mt-1">
                            <span>{{ number_format($video->views_count ?? 0) }} views</span>
                            <span class="mx-2">•</span>
                            <span>{{ $video->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex items-center mt-2">
                            @if($video->user->avatar)
                                <img src="{{ $video->user->avatar_url }}" alt="{{ $video->user->name }}" class="w-6 h-6 rounded-full object-cover mr-2">
                            @else
                                <div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center text-white text-xs font-medium mr-2">
                                    {{ strtoupper(substr($video->user->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="text-sm text-gray-400">{{ $video->user->name }}</span>
                        </div>
                        @if($video->description)
                            <p class="text-sm text-gray-500 mt-2 line-clamp-2">{{ $video->description }}</p>
                        @endif
                    </div>
                </a>
            @empty
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-gray-400 text-lg">No videos found for "{{ $query }}"</p>
                    <p class="text-gray-500 text-sm mt-2">Try different keywords</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $videos->appends(request()->query())->links() }}
        </div>
    </div>
</x-main-layout>
