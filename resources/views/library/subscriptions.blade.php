<x-main-layout>
    <x-slot name="title">Subscriptions - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8">Subscriptions</h1>

        @if($channels->count() > 0)
            <div class="pt-video-grid">
                @foreach($channels as $channel)
                    <a href="{{ route('channel.show', $channel->username) }}" class="group bg-gray-800 rounded-xl p-6 text-center hover:bg-gray-750 transition-colors">
                        @if($channel->avatar_path)
                            <img src="{{ $channel->avatar_url }}" alt="{{ $channel->name }}" class="w-20 h-20 rounded-full mx-auto mb-4 object-cover">
                        @else
                            <div class="w-20 h-20 rounded-full bg-red-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-4">
                                {{ strtoupper(substr($channel->name, 0, 1)) }}
                            </div>
                        @endif
                        <h3 class="font-medium text-white group-hover:text-gray-300">{{ $channel->name }}</h3>
                        <p class="text-sm text-gray-400 mt-1">{{ number_format($channel->videos_count ?? 0) }} videos</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $channels->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-400 mb-2">No subscriptions yet</h3>
                <p class="text-gray-500">Channels you subscribe to will appear here</p>
                <a href="{{ route('home') }}" class="inline-block mt-4 px-6 py-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors">
                    Discover channels
                </a>
            </div>
        @endif
    </div>
</x-main-layout>
