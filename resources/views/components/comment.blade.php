@props(['comment', 'level' => 0])

<div class="flex gap-3 {{ $level > 0 ? 'ml-12' : '' }}" x-data="commentReaction({{ $comment->id }}, {{ $comment->likes_count ?? 0 }}, {{ $comment->dislikes_count ?? 0 }})">
    <a href="{{ route('channel.show', $comment->user->username) }}" class="flex-shrink-0">
        @if($comment->user->avatar)
            <img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}" class="w-10 h-10 rounded-full object-cover">
        @else
            <div class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white font-medium">
                {{ strtoupper(substr($comment->user->name, 0, 1)) }}
            </div>
        @endif
    </a>
    
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('channel.show', $comment->user->username) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                {{ $comment->user->name }}
            </a>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
        </div>
        
        <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap break-words">{{ $comment->body }}</p>
        
        <div class="flex items-center gap-4 mt-2">
            @auth
                <button 
                    type="button" 
                    @click="react('like')"
                    :disabled="reacting"
                    class="flex items-center gap-1 text-sm transition-colors"
                    :class="userReaction === 'like' ? 'text-blue-500' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                >
                    <svg class="w-4 h-4" :fill="userReaction === 'like' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                    <span x-text="likesCount"></span>
                </button>
                
                <button 
                    type="button" 
                    @click="react('dislike')"
                    :disabled="reacting"
                    class="flex items-center gap-1 text-sm transition-colors"
                    :class="userReaction === 'dislike' ? 'text-blue-500' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                >
                    <svg class="w-4 h-4" :fill="userReaction === 'dislike' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                    </svg>
                </button>
                
                @if($level < 1)
                <button 
                    type="button" 
                    class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                    x-data="{ showReply: false }"
                    @click="showReply = !showReply"
                >
                    Reply
                </button>
                @endif
            @else
                <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                    <span>{{ $comment->likes_count ?? 0 }}</span>
                </span>
            @endauth
        </div>

        {{-- Replies --}}
        @if($comment->replies && $comment->replies->count() > 0)
            <div class="mt-3 space-y-3" x-data="{ showReplies: false }">
                <button @click="showReplies = !showReplies" class="flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showReplies }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    <span x-text="showReplies ? 'Hide' : 'View'"></span> {{ $comment->replies->count() }} {{ Str::plural('reply', $comment->replies->count()) }}
                </button>
                
                <div x-show="showReplies" x-collapse class="space-y-3">
                    @foreach($comment->replies as $reply)
                        @include('components.comment', ['comment' => $reply, 'level' => $level + 1])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
    function commentReaction(commentId, initialLikes, initialDislikes) {
        return {
            commentId: commentId,
            likesCount: initialLikes,
            dislikesCount: initialDislikes,
            userReaction: null,
            reacting: false,
            
            async react(type) {
                if (this.reacting) return;
                this.reacting = true;
                
                try {
                    const response = await fetch(`/api/v1/comments/${this.commentId}/react`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ reaction: type })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.likesCount = data.likes_count;
                        this.dislikesCount = data.dislikes_count;
                        this.userReaction = data.reaction;
                    }
                } catch (e) {
                    console.error('Comment reaction failed:', e);
                } finally {
                    this.reacting = false;
                }
            }
        };
    }
</script>
@endpush
@endonce
