<x-main-layout>
    <x-slot name="title">Creator Studio - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-white">Creator Studio</h1>
            @if(auth()->user()->isCreator())
                <div class="flex items-center gap-2">
                    <a href="{{ route('studio.embed') }}" class="flex items-center px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Embed Video
                    </a>
                    <a href="{{ route('studio.upload') }}" class="flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Upload Video
                    </a>
                </div>
            @else
                <a href="{{ route('studio.upload') }}" class="flex items-center px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Request Upload Access
                </a>
            @endif
        </div>

        <!-- Creator Status Banner -->
        @if(!auth()->user()->isCreator())
            @php
                $latestRequest = auth()->user()->latestCreatorRequest;
            @endphp
            <div class="mb-6 p-4 rounded-xl {{ $latestRequest && $latestRequest->status === 'pending' ? 'bg-yellow-500/10 border border-yellow-500/50' : ($latestRequest && $latestRequest->status === 'rejected' ? 'bg-red-500/10 border border-red-500/50' : 'bg-blue-500/10 border border-blue-500/50') }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        @if($latestRequest && $latestRequest->status === 'pending')
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-yellow-500 font-medium">Creator Request Pending</p>
                                <p class="text-gray-400 text-sm">Your request is being reviewed. You'll be notified once approved.</p>
                            </div>
                        @elseif($latestRequest && $latestRequest->status === 'rejected')
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-red-500 font-medium">Creator Request Rejected</p>
                                <p class="text-gray-400 text-sm">{{ $latestRequest->admin_notes ?? 'You can submit a new request.' }}</p>
                            </div>
                        @else
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-blue-400 font-medium">Want to upload videos?</p>
                                <p class="text-gray-400 text-sm">Request creator access to start sharing content on PlayTube.</p>
                            </div>
                        @endif
                    </div>
                    <a href="{{ route('studio.upload') }}" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg transition-colors">
                        {{ $latestRequest && $latestRequest->status === 'pending' ? 'View Status' : 'Request Access' }}
                    </a>
                </div>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-400">Total Views</h3>
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-white">{{ number_format($totalViews) }}</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-400">Subscribers</h3>
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-white">{{ number_format($subscriberCount) }}</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-400">Videos</h3>
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-white">{{ number_format($videoCount) }}</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-400">Total Likes</h3>
                    <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-white">{{ number_format($totalLikes) }}</p>
            </div>
        </div>

        <!-- Background Optimization Status (Non-blocking, informational only) -->
        @php
            $optimizingVideos = auth()->user()->videos()
                ->whereIn('processing_state', ['pending', 'processing'])
                ->latest()
                ->get();
        @endphp
        @if($optimizingVideos->count() > 0)
        <div class="bg-blue-900/20 border border-blue-600 rounded-xl p-4 mb-8">
            <div class="flex items-center mb-3">
                <svg class="w-5 h-5 text-blue-400 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <h3 class="text-sm font-medium text-blue-300">Optimizing {{ $optimizingVideos->count() }} video(s) in background</h3>
            </div>
            <p class="text-xs text-blue-400/70 mb-3">Videos are already published and watchable. Optimization improves streaming quality.</p>
            <div class="flex flex-wrap gap-2">
                @foreach($optimizingVideos as $optVideo)
                <a href="{{ route('studio.edit', $optVideo) }}" class="inline-flex items-center px-3 py-1 bg-blue-900/50 rounded-full text-xs text-blue-200 hover:bg-blue-800/50">
                    {{ Str::limit($optVideo->title, 20) }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Recent Videos -->
        <div class="bg-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-white">Recent Videos</h2>
                <a href="{{ route('studio.videos') }}" class="text-sm text-blue-400 hover:text-blue-300">View All</a>
            </div>

            @if($recentVideos->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-400 border-b border-gray-700">
                                <th class="pb-3 font-medium">Video</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Views</th>
                                <th class="pb-3 font-medium">Likes</th>
                                <th class="pb-3 font-medium">Comments</th>
                                <th class="pb-3 font-medium">Date</th>
                                <th class="pb-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($recentVideos as $video)
                                <tr>
                                    <td class="py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-24 aspect-video bg-gray-700 rounded overflow-hidden flex-shrink-0">
                                                @if($video->has_thumbnail)
                                                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" loading="lazy" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-white truncate max-w-xs">{{ $video->title }}</p>
                                                <p class="text-xs text-gray-400">{{ Str::limit($video->description, 50) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            @if($video->status === 'published') bg-green-900 text-green-300
                                            @elseif($video->status === 'processing') bg-yellow-900 text-yellow-300
                                            @elseif($video->status === 'failed') bg-red-900 text-red-300
                                            @else bg-gray-700 text-gray-300
                                            @endif">
                                            {{ ucfirst($video->status) }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-sm text-gray-300">{{ number_format($video->views_count ?? 0) }}</td>
                                    <td class="py-4 text-sm text-gray-300">{{ number_format($video->likes_count ?? 0) }}</td>
                                    <td class="py-4 text-sm text-gray-300">{{ number_format($video->comments_count ?? 0) }}</td>
                                    <td class="py-4 text-sm text-gray-400">{{ $video->created_at->format('M d, Y') }}</td>
                                    <td class="py-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('studio.edit', $video) }}" class="p-1 text-gray-400 hover:text-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            <a href="{{ route('video.watch', $video->slug) }}" class="p-1 text-gray-400 hover:text-white" target="_blank">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                            </a>
                                            <form action="{{ route('studio.destroy', $video) }}" method="POST" class="inline" onsubmit="return confirm('Delete this video?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-gray-400 hover:text-red-500">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-400 mb-4">No videos yet</p>
                    <a href="{{ route('studio.upload') }}" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Upload Your First Video
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-main-layout>
