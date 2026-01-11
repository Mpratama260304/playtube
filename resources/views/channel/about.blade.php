<x-main-layout>
    <x-slot name="title">{{ $channel->name }} - About - {{ config('app.name') }}</x-slot>

    <!-- Channel Header Mini -->
    <div class="flex items-center space-x-4 mb-6 p-4 bg-gray-800 rounded-xl">
        @if($channel->avatar_path)
            <img src="{{ $channel->avatar_url }}" alt="{{ $channel->name }}" class="w-16 h-16 rounded-full object-cover">
        @else
            <div class="w-16 h-16 rounded-full bg-red-600 flex items-center justify-center text-white text-2xl font-bold">
                {{ strtoupper(substr($channel->name, 0, 1)) }}
            </div>
        @endif
        <div class="flex-1">
            <h1 class="text-xl font-bold text-white">{{ $channel->name }}</h1>
            <p class="text-gray-400 text-sm">{{ '@' . $channel->username }} • {{ number_format($channel->subscribers_count) }} subscribers</p>
        </div>
        <a href="{{ route('channel.show', $channel->username) }}" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600">
            View Channel
        </a>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-800 mb-6">
        <nav class="flex space-x-8">
            <a href="{{ route('channel.show', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Home</a>
            <a href="{{ route('channel.videos', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Videos</a>
            <a href="{{ route('channel.shorts', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Shorts</a>
            <a href="{{ route('channel.playlists', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-white">Playlists</a>
            <a href="{{ route('channel.about', $channel->username) }}" class="py-4 text-sm font-medium border-b-2 border-white text-white">About</a>
        </nav>
    </div>

    <!-- About Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Description -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-gray-800 rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4">Description</h2>
                @if($channel->bio)
                    <p class="text-gray-300 whitespace-pre-line">{{ $channel->bio }}</p>
                @else
                    <p class="text-gray-500 italic">No description available.</p>
                @endif
            </div>
        </div>

        <!-- Stats -->
        <div class="space-y-6">
            <div class="bg-gray-800 rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4">Stats</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Joined</span>
                        <span class="text-white">{{ $channel->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Subscribers</span>
                        <span class="text-white">{{ number_format($channel->subscribers_count) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Videos</span>
                        <span class="text-white">{{ number_format($videosCount) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Total Views</span>
                        <span class="text-white">{{ number_format($totalViews) }}</span>
                    </div>
                </div>
            </div>

            <!-- Links -->
            @if($channel->website || $channel->twitter || $channel->instagram)
            <div class="bg-gray-800 rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4">Links</h2>
                <div class="space-y-3">
                    @if($channel->website)
                    <a href="{{ $channel->website }}" target="_blank" class="flex items-center space-x-3 text-blue-400 hover:text-blue-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <span>Website</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</x-main-layout>
