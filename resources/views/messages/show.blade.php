<x-main-layout>
    <x-slot name="title">Messages with {{ $otherUser->name }} - {{ config('app.name') }}</x-slot>

    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center space-x-4 mb-6 pb-4 border-b border-gray-800">
            <a href="{{ route('messages.index') }}" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="{{ route('channel.show', $otherUser->username) }}" class="flex items-center space-x-3">
                @if($otherUser->avatar_path)
                    <img src="{{ $otherUser->avatar_url }}" alt="{{ $otherUser->name }}" class="w-10 h-10 rounded-full object-cover">
                @else
                    <div class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr($otherUser->name, 0, 1)) }}
                    </div>
                @endif
                <span class="font-medium text-white">{{ $otherUser->name }}</span>
            </a>
        </div>

        <!-- Messages -->
        <div class="space-y-4 mb-6 min-h-[400px] max-h-[600px] overflow-y-auto" id="messages-container">
            @forelse($messages->reverse() as $message)
                <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[70%] {{ $message->sender_id === auth()->id() ? 'bg-red-600' : 'bg-gray-800' }} rounded-2xl px-4 py-2">
                        <p class="text-white">{{ $message->body }}</p>
                        <p class="text-xs {{ $message->sender_id === auth()->id() ? 'text-red-200' : 'text-gray-500' }} mt-1">
                            {{ $message->created_at->format('M d, g:i a') }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500">
                    No messages yet. Start the conversation!
                </div>
            @endforelse
        </div>

        <!-- Message Form -->
        <form action="{{ route('messages.store', $conversation) }}" method="POST" class="flex space-x-4">
            @csrf
            <input type="text" name="body" placeholder="Type a message..." required
                   class="flex-1 px-4 py-3 bg-gray-800 border border-gray-700 rounded-full text-white placeholder-gray-400 focus:outline-none focus:border-gray-600">
            <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors">
                Send
            </button>
        </form>
    </div>

    @push('scripts')
    <script>
        // Scroll to bottom of messages
        const container = document.getElementById('messages-container');
        if (container) container.scrollTop = container.scrollHeight;
    </script>
    @endpush
</x-main-layout>
