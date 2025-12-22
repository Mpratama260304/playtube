<x-main-layout>
    <x-slot name="title">My Videos - Creator Studio - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-white">My Videos</h1>
            <a href="{{ route('studio.upload') }}" class="flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Upload Video
            </a>
        </div>

        @if($videos->count() > 0)
            <div class="bg-gray-800 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-400 border-b border-gray-700">
                                <th class="px-6 py-4 font-medium">Video</th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-6 py-4 font-medium">Visibility</th>
                                <th class="px-6 py-4 font-medium">Views</th>
                                <th class="px-6 py-4 font-medium">Likes</th>
                                <th class="px-6 py-4 font-medium">Comments</th>
                                <th class="px-6 py-4 font-medium">Date</th>
                                <th class="px-6 py-4 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($videos as $video)
                                <tr class="hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-32 aspect-video bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
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
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-white truncate max-w-xs">{{ $video->title }}</p>
                                                <p class="text-xs text-gray-400 mt-1">{{ Str::limit($video->description, 60) }}</p>
                                                @if($video->category)
                                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-gray-700 text-gray-300 rounded">{{ $video->category->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            @if($video->status === 'published') bg-green-900 text-green-300
                                            @elseif($video->status === 'processing') bg-yellow-900 text-yellow-300
                                            @elseif($video->status === 'failed') bg-red-900 text-red-300
                                            @else bg-gray-700 text-gray-300
                                            @endif">
                                            {{ ucfirst($video->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-300 capitalize">{{ $video->visibility ?? 'public' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">{{ number_format($video->views_count ?? 0) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-300">{{ number_format($video->likes_count ?? 0) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-300">{{ number_format($video->comments_count ?? 0) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-400">{{ $video->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('studio.edit', $video) }}" class="p-2 text-gray-400 hover:text-white hover:bg-gray-600 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            @if($video->status === 'published')
                                                <a href="{{ route('video.watch', $video->slug) }}" class="p-2 text-gray-400 hover:text-white hover:bg-gray-600 rounded-lg transition-colors" target="_blank" title="View">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                </a>
                                            @endif
                                            <form action="{{ route('studio.destroy', $video) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this video? This action cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-gray-600 rounded-lg transition-colors" title="Delete">
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
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $videos->links() }}
            </div>
        @else
            <div class="bg-gray-800 rounded-xl p-12 text-center">
                <svg class="w-20 h-20 mx-auto text-gray-600 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <h3 class="text-xl font-medium text-white mb-2">No videos yet</h3>
                <p class="text-gray-400 mb-6">Start sharing your content with the world</p>
                <a href="{{ route('studio.upload') }}" class="inline-flex items-center px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Upload Your First Video
                </a>
            </div>
        @endif
    </div>
</x-main-layout>
