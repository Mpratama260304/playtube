<x-main-layout>
    <x-slot name="title">Watch Later - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8">Watch Later</h1>

        @if($videos->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($videos as $video)
                    @include('components.video-card', ['video' => $video])
                @endforeach
            </div>

            <div class="mt-8">
                {{ $videos->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-400 mb-2">No videos in Watch Later</h3>
                <p class="text-gray-500">Save videos to watch later by clicking the clock icon</p>
            </div>
        @endif
    </div>
</x-main-layout>
