<x-main-layout>
    <x-slot name="title">Analytics - Creator Studio - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8">Channel Analytics</h1>

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-400">Total Views</h3>
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ number_format($totalViews) }}</p>
                <p class="text-sm text-gray-400 mt-1">Lifetime</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-400">Subscribers</h3>
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ number_format($subscriberCount) }}</p>
                <p class="text-sm text-gray-400 mt-1">Total subscribers</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-400">Videos</h3>
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ number_format($videoCount) }}</p>
                <p class="text-sm text-gray-400 mt-1">Total uploads</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-400">Total Likes</h3>
                    <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ number_format($totalLikes) }}</p>
                <p class="text-sm text-gray-400 mt-1">On all videos</p>
            </div>
        </div>

        <!-- Top Videos -->
        <div class="bg-gray-800 rounded-xl p-6 mb-8">
            <h2 class="text-lg font-bold text-white mb-6">Top Videos</h2>
            
            @if($videos->count() > 0)
                <div class="space-y-4">
                    @foreach($videos->sortByDesc('views_count') as $video)
                        <div class="flex items-center space-x-4 p-4 bg-gray-700/50 rounded-lg">
                            <div class="w-40 aspect-video bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                @if($video->has_thumbnail)
                                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" loading="lazy" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-medium text-white truncate">{{ $video->title }}</h3>
                                <p class="text-xs text-gray-400 mt-1">{{ $video->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="grid grid-cols-3 gap-8 text-center">
                                <div>
                                    <p class="text-lg font-bold text-white">{{ number_format($video->views_count ?? 0) }}</p>
                                    <p class="text-xs text-gray-400">Views</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white">{{ number_format($video->likes_count ?? 0) }}</p>
                                    <p class="text-xs text-gray-400">Likes</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white">{{ number_format($video->comments_count ?? 0) }}</p>
                                    <p class="text-xs text-gray-400">Comments</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-gray-400">No analytics data yet. Upload some videos to see stats.</p>
                </div>
            @endif
        </div>

        <!-- Coming Soon -->
        <div class="bg-gray-800 rounded-xl p-6">
            <h2 class="text-lg font-bold text-white mb-4">More Analytics Coming Soon</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 border border-gray-700 rounded-lg">
                    <svg class="w-8 h-8 text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    <h3 class="font-medium text-white">Traffic Sources</h3>
                    <p class="text-sm text-gray-400">See where your viewers are coming from</p>
                </div>
                <div class="p-4 border border-gray-700 rounded-lg">
                    <svg class="w-8 h-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h3 class="font-medium text-white">Geographic Data</h3>
                    <p class="text-sm text-gray-400">Understand your audience by location</p>
                </div>
                <div class="p-4 border border-gray-700 rounded-lg">
                    <svg class="w-8 h-8 text-purple-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="font-medium text-white">Watch Time</h3>
                    <p class="text-sm text-gray-400">Track how long viewers watch your content</p>
                </div>
            </div>
        </div>
    </div>
</x-main-layout>
