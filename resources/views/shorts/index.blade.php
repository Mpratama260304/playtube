<x-main-layout>
    <x-slot name="title">Shorts - {{ config('app.name') }}</x-slot>

    <div class="max-w-lg mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8 text-center">Shorts</h1>

        @if($shorts->count() > 0)
            <div class="space-y-6">
                @foreach($shorts as $short)
                    <div class="bg-gray-800 rounded-xl overflow-hidden">
                        <a href="{{ route('shorts.show', $short->slug) }}" class="block">
                            <div class="relative aspect-[9/16] max-h-[70vh] bg-black">
                                <video 
                                    class="w-full h-full object-cover"
                                    poster="{{ $short->thumbnail_url }}"
                                    muted
                                    loop
                                    playsinline
                                    onmouseenter="this.play()"
                                    onmouseleave="this.pause(); this.currentTime = 0;"
                                >
                                    <source src="{{ $short->video_url }}" type="video/mp4">
                                </video>
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity bg-black/20">
                                    <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- Info -->
                        <div class="p-4">
                            <div class="flex items-start space-x-3">
                                <a href="{{ route('channel.show', $short->user->username) }}">
                                    @if($short->user->avatar)
                                        <img src="{{ $short->user->avatar_url }}" alt="{{ $short->user->name }}" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center text-white font-medium">
                                            {{ strtoupper(substr($short->user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-white">{{ $short->title }}</h3>
                                    <p class="text-sm text-gray-400">{{ $short->user->name }}</p>
                                    <p class="text-sm text-gray-500">{{ number_format($short->views_count ?? 0) }} views</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $shorts->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-lg">No shorts yet</p>
            </div>
        @endif
    </div>
</x-main-layout>
