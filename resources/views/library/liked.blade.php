<x-main-layout>
    <x-slot name="title">Liked Videos - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8">Liked Videos</h1>

        @if($videos->count() > 0)
            {{-- Mobile: Compact row list --}}
            <div class="block sm:hidden space-y-1">
                @foreach($videos as $video)
                    <x-video-row :video="$video" />
                @endforeach
            </div>

            {{-- Tablet & Desktop: Grid cards --}}
            <div class="hidden sm:grid pt-video-grid">
                @foreach($videos as $video)
                    <x-video-card :video="$video" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $videos->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-400 mb-2">No liked videos</h3>
                <p class="text-gray-500">Videos you like will appear here</p>
            </div>
        @endif
    </div>
</x-main-layout>
