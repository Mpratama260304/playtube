<x-main-layout>
    <x-slot name="title">Messages - {{ config('app.name') }}</x-slot>

    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8">Messages</h1>

        @if($conversations->count() > 0)
            <div class="space-y-2">
                @foreach($conversations as $conversation)
                    @php
                        $otherUser = $conversation->user_one_id === auth()->id() 
                            ? $conversation->userTwo 
                            : $conversation->userOne;
                    @endphp
                    <a href="{{ route('messages.show', $conversation) }}" class="flex items-center space-x-4 p-4 bg-gray-800 rounded-xl hover:bg-gray-750 transition-colors">
                        @if($otherUser->avatar_path)
                            <img src="{{ $otherUser->avatar_url }}" alt="{{ $otherUser->name }}" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <div class="w-12 h-12 rounded-full bg-red-600 flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($otherUser->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium text-white truncate">{{ $otherUser->name }}</h3>
                                @if($conversation->latestMessage)
                                    <span class="text-xs text-gray-500">{{ $conversation->latestMessage->created_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            @if($conversation->latestMessage)
                                <p class="text-sm text-gray-400 truncate">
                                    @if($conversation->latestMessage->sender_id === auth()->id())
                                        You: 
                                    @endif
                                    {{ $conversation->latestMessage->body }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $conversations->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-400 mb-2">No messages yet</h3>
                <p class="text-gray-500">Start a conversation with other users</p>
            </div>
        @endif
    </div>
</x-main-layout>
