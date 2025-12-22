<!-- Comment Component -->
<div class="flex space-x-3" id="comment-{{ $comment->id }}">
    <a href="{{ route('channel.show', $comment->user->username) }}">
        @if($comment->user->avatar)
            <img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}" class="w-10 h-10 rounded-full object-cover">
        @else
            <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-white text-sm font-medium">
                {{ strtoupper(substr($comment->user->name, 0, 1)) }}
            </div>
        @endif
    </a>
    <div class="flex-1">
        <div class="flex items-center space-x-2 mb-1">
            <a href="{{ route('channel.show', $comment->user->username) }}" class="text-sm font-medium text-white hover:text-gray-300">
                {{ $comment->user->name }}
            </a>
            <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
        </div>
        <p class="text-sm text-gray-300">{{ $comment->body }}</p>
        
        <div class="flex items-center space-x-4 mt-2">
            @auth
                <!-- Like/Dislike Comment -->
                <button class="flex items-center text-gray-400 hover:text-white text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                    {{ $comment->likes_count ?? 0 }}
                </button>
                <button class="text-gray-400 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                    </svg>
                </button>
                <button class="text-sm text-gray-400 hover:text-white" x-data @click="$refs.replyForm{{ $comment->id }}.classList.toggle('hidden')">
                    Reply
                </button>
            @endauth

            @can('delete', $comment)
                <form action="{{ route('comment.delete', $comment) }}" method="POST" class="inline" onsubmit="return confirm('Delete this comment?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-gray-400 hover:text-red-500">Delete</button>
                </form>
            @endcan
        </div>

        @auth
            <!-- Reply Form (hidden by default) -->
            <form action="{{ route('video.comment', $comment->video) }}" method="POST" class="mt-3 hidden" x-ref="replyForm{{ $comment->id }}">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                <div class="flex space-x-3">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover">
                    @else
                        <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="body" 
                            placeholder="Add a reply..." 
                            class="w-full px-3 py-2 bg-transparent border-b border-gray-700 text-white placeholder-gray-400 text-sm focus:outline-none focus:border-gray-500"
                            required
                        >
                        <div class="flex justify-end mt-2 space-x-2">
                            <button type="button" @click="$refs.replyForm{{ $comment->id }}.classList.add('hidden')" class="px-3 py-1 text-sm text-gray-400 hover:text-white">Cancel</button>
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-full text-sm font-medium hover:bg-blue-700">Reply</button>
                        </div>
                    </div>
                </div>
            </form>
        @endauth

        <!-- Replies -->
        @if($comment->replies && $comment->replies->count() > 0)
            <div class="mt-4 space-y-3" x-data="{ showReplies: false }">
                <button @click="showReplies = !showReplies" class="flex items-center text-sm text-blue-400 hover:text-blue-300">
                    <svg class="w-4 h-4 mr-1 transition-transform" :class="{ 'rotate-180': showReplies }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    {{ $comment->replies->count() }} {{ Str::plural('reply', $comment->replies->count()) }}
                </button>
                <div x-show="showReplies" x-transition class="space-y-3 ml-4">
                    @foreach($comment->replies as $reply)
                        @include('components.comment', ['comment' => $reply])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
