<x-main-layout>
    <x-slot name="title">Watch History - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-white">Watch History</h1>
            @if($history->count() > 0)
                <form action="{{ route('library.clear-history') }}" method="POST" onsubmit="return confirm('Clear all watch history?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-400 hover:text-red-300">Clear all history</button>
                </form>
            @endif
        </div>

        @if($history->count() > 0)
            <div class="space-y-4">
                @foreach($history as $item)
                    @if($item->video)
                        <div class="flex space-x-4 bg-gray-800 rounded-xl p-4">
                            <a href="{{ route('video.watch', $item->video) }}" class="flex-shrink-0">
                                <div class="relative w-40 aspect-video bg-gray-700 rounded-lg overflow-hidden">
                                    @if($item->video->has_thumbnail)
                                        <img src="{{ $item->video->thumbnail_url }}" alt="{{ $item->video->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    @if($item->video->duration)
                                        <span class="absolute bottom-1 right-1 bg-black/80 text-white text-xs px-1 rounded">
                                            {{ $item->video->formatted_duration }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('video.watch', $item->video) }}" class="block">
                                    <h3 class="font-medium text-white hover:text-gray-300 line-clamp-2">{{ $item->video->title }}</h3>
                                </a>
                                <a href="{{ route('channel.show', $item->video->user->username) }}" class="text-sm text-gray-400 hover:text-gray-300 mt-1 block">
                                    {{ $item->video->user->name }}
                                </a>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ number_format($item->video->views_count) }} views • Watched {{ $item->watched_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-8">
                {{ $history->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-400 mb-2">No watch history</h3>
                <p class="text-gray-500">Videos you watch will appear here</p>
            </div>
        @endif
    </div>
</x-main-layout>
