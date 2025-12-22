<x-main-layout>
    <x-slot name="title">{{ $playlist->name }} - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Playlist Info -->
            <div class="lg:w-80 flex-shrink-0">
                <div class="bg-gray-800 rounded-xl overflow-hidden sticky top-20">
                    <!-- Cover -->
                    <div class="relative aspect-video bg-gray-700">
                        @if($playlist->videos->first() && $playlist->videos->first()->thumbnail)
                            <img src="{{ $playlist->videos->first()->thumbnail_url }}" alt="{{ $playlist->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40"></div>
                        <div class="absolute bottom-4 left-4 right-4">
                            <h1 class="text-xl font-bold text-white">{{ $playlist->name }}</h1>
                            <p class="text-sm text-gray-300 mt-1">{{ $playlist->user->name }}</p>
                        </div>
                    </div>
                    
                    <!-- Info -->
                    <div class="p-4">
                        <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                            <span>{{ $playlist->videos->count() }} videos</span>
                            <span>{{ ucfirst($playlist->visibility) }}</span>
                        </div>
                        
                        @if($playlist->description)
                            <p class="text-sm text-gray-300 mb-4">{{ $playlist->description }}</p>
                        @endif
                        
                        @if($playlist->videos->count() > 0)
                            <a href="{{ route('video.watch', $playlist->videos->first()->slug) }}?list={{ $playlist->id }}" 
                               class="flex items-center justify-center w-full px-4 py-2 bg-white text-gray-900 rounded-lg hover:bg-gray-200 font-medium">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                Play All
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Videos List -->
            <div class="flex-1">
                @if($playlist->videos->count() > 0)
                    <div class="space-y-3">
                        @foreach($playlist->videos as $index => $video)
                            <div class="flex space-x-3 group bg-gray-800/50 hover:bg-gray-800 rounded-lg p-2">
                                <span class="w-6 flex-shrink-0 text-center text-gray-500 self-center">{{ $index + 1 }}</span>
                                <a href="{{ route('video.watch', $video->slug) }}?list={{ $playlist->id }}" class="flex space-x-3 flex-1 min-w-0">
                                    <div class="relative w-40 aspect-video bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                        @if($video->has_thumbnail)
                                            <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                        @endif
                                        @if($video->duration_seconds)
                                            <div class="absolute bottom-1 right-1 px-1 py-0.5 bg-black/80 rounded text-xs">
                                                {{ $video->formatted_duration }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0 py-1">
                                        <h3 class="font-medium text-white line-clamp-2 group-hover:text-gray-300">{{ $video->title }}</h3>
                                        <p class="text-sm text-gray-400 mt-1">{{ $video->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($video->views_count ?? 0) }} views</p>
                                    </div>
                                </a>
                                
                                @can('update', $playlist)
                                    <form action="{{ route('playlists.remove-video', [$playlist, $video]) }}" method="POST" class="self-center">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-400">This playlist is empty</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-main-layout>
