<x-main-layout>
    <x-slot name="title">{{ $video->title }} - Shorts</x-slot>

    <div class="fixed inset-0 bg-black z-50" x-data="shortsPlayer()">
        <!-- Close button -->
        <a href="{{ route('shorts.index') }}" class="absolute top-4 left-4 z-50 p-2 text-white/80 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </a>

        <!-- Logo -->
        <div class="absolute top-4 left-1/2 -translate-x-1/2 z-50">
            <a href="{{ route('home') }}" class="text-white font-bold text-xl flex items-center">
                <svg class="w-8 h-8 text-red-500 mr-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                </svg>
                Shorts
            </a>
        </div>

        <!-- Main content -->
        <div class="h-full flex items-center justify-center">
            <div class="flex items-center gap-4">
                <!-- Video container -->
                <div class="relative w-full max-w-[360px] md:max-w-[380px]">
                    <div class="relative aspect-[9/16] bg-gray-900 rounded-xl md:rounded-2xl overflow-hidden shadow-2xl">
                        <video 
                            x-ref="video"
                            class="w-full h-full object-cover"
                            src="{{ route('video.stream', $video->uuid) }}"
                            poster="{{ $video->thumbnail_url }}"
                            playsinline
                            loop
                            @click="togglePlay()"
                            @ended="onEnded()"
                        ></video>

                        <!-- Play/Pause overlay -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none" x-show="showPlayIcon" x-transition.opacity>
                            <div class="w-16 h-16 rounded-full bg-black/50 flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Video info overlay (bottom) -->
                        <div class="absolute bottom-0 left-0 right-0 p-3 md:p-4 pr-14" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 50%, transparent 100%);">
                            <!-- Channel info -->
                            <div class="flex items-center space-x-2 mb-2">
                                <a href="{{ route('channel.show', $video->user->username) }}" class="flex-shrink-0">
                                    @if($video->user->avatar_path)
                                        <img src="{{ $video->user->avatar_url }}" alt="{{ $video->user->name }}" class="w-8 h-8 rounded-full object-cover border-2 border-white">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-xs font-bold border-2 border-white">
                                            {{ strtoupper(substr($video->user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('channel.show', $video->user->username) }}" class="text-white text-sm font-medium hover:underline truncate block">
                                        {{ '@' . $video->user->username }}
                                    </a>
                                </div>
                                @auth
                                    @if(auth()->id() !== $video->user_id)
                                        <button 
                                            @click="subscribe()"
                                            class="px-3 py-1 text-xs font-medium rounded-full transition-colors flex-shrink-0"
                                            :class="subscribed ? 'bg-gray-600 text-white' : 'bg-red-600 text-white hover:bg-red-700'"
                                        >
                                            <span x-text="subscribed ? 'Subscribed' : 'Subscribe'"></span>
                                        </button>
                                    @endif
                                @endauth
                            </div>

                            <!-- Title -->
                            <h1 class="text-white text-sm font-medium mb-1 line-clamp-2">{{ $video->title }}</h1>

                            <!-- Stats -->
                            <div class="flex items-center space-x-3 text-xs text-gray-300">
                                <span>{{ number_format($video->views_count) }} views</span>
                                <span>{{ $video->published_at?->diffForHumans() ?? $video->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <!-- Mobile Action buttons (inside video) - visible on mobile only -->
                        <div class="md:hidden absolute right-2 bottom-28 flex flex-col items-center space-y-3">
                            <!-- Like -->
                            <button @click="react('like')" class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="userReaction === 'like' ? 'bg-red-500' : 'bg-black/50'">
                                    <svg class="w-5 h-5 text-white" :fill="userReaction === 'like' ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                    </svg>
                                </div>
                                <span class="text-white text-[10px] mt-0.5" x-text="formatCount(likeCount)"></span>
                            </button>
                            <!-- Dislike -->
                            <button @click="react('dislike')" class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="userReaction === 'dislike' ? 'bg-gray-600' : 'bg-black/50'">
                                    <svg class="w-5 h-5 text-white" :fill="userReaction === 'dislike' ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                                    </svg>
                                </div>
                                <span class="text-white text-[10px] mt-0.5" x-text="formatCount(dislikeCount)"></span>
                            </button>
                            <!-- Comment -->
                            <button @click="showComments = true" class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full bg-black/50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <span class="text-white text-[10px] mt-0.5" x-text="formatCount(commentCount)"></span>
                            </button>
                            <!-- Share -->
                            <button @click="shareVideo()" class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full bg-black/50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                    </svg>
                                </div>
                                <span class="text-white text-[10px] mt-0.5">Share</span>
                            </button>
                            <!-- Mute -->
                            <button @click="toggleMute()" class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full bg-black/50 flex items-center justify-center">
                                    <template x-if="!muted">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                        </svg>
                                    </template>
                                    <template x-if="muted">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                                        </svg>
                                    </template>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Desktop Action buttons (outside video, right side) - hidden on mobile -->
                <div class="hidden md:flex flex-col items-center space-y-4">
                    <!-- Like -->
                    <button @click="react('like')" class="flex flex-col items-center group">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all group-hover:scale-110" :class="userReaction === 'like' ? 'bg-red-500' : 'bg-gray-800 group-hover:bg-gray-700'">
                            <svg class="w-6 h-6 text-white" :fill="userReaction === 'like' ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                            </svg>
                        </div>
                        <span class="text-white text-xs mt-1" x-text="formatCount(likeCount)"></span>
                    </button>
                    <!-- Dislike -->
                    <button @click="react('dislike')" class="flex flex-col items-center group">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all group-hover:scale-110" :class="userReaction === 'dislike' ? 'bg-gray-600' : 'bg-gray-800 group-hover:bg-gray-700'">
                            <svg class="w-6 h-6 text-white" :fill="userReaction === 'dislike' ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                            </svg>
                        </div>
                        <span class="text-white text-xs mt-1" x-text="formatCount(dislikeCount)"></span>
                    </button>
                    <!-- Comment -->
                    <button @click="showComments = true" class="flex flex-col items-center group">
                        <div class="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center transition-all group-hover:scale-110 group-hover:bg-gray-700">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <span class="text-white text-xs mt-1" x-text="formatCount(commentCount)"></span>
                    </button>
                    <!-- Share -->
                    <button @click="shareVideo()" class="flex flex-col items-center group">
                        <div class="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center transition-all group-hover:scale-110 group-hover:bg-gray-700">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                        </div>
                        <span class="text-white text-xs mt-1">Share</span>
                    </button>
                    <!-- Mute -->
                    <button @click="toggleMute()" class="flex flex-col items-center group">
                        <div class="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center transition-all group-hover:scale-110 group-hover:bg-gray-700">
                            <template x-if="!muted">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                </svg>
                            </template>
                            <template x-if="muted">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                                </svg>
                            </template>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation arrows - Only on desktop -->
        @if($moreShorts->count() > 0)
            <button @click="nextShort()" class="hidden md:block absolute right-8 top-1/2 -translate-y-1/2 p-3 bg-gray-800 hover:bg-gray-700 rounded-full text-white transition-all duration-200 hover:scale-110" style="box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </button>
        @endif

        <!-- Comments panel -->
        <div 
            x-show="showComments" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            class="fixed inset-x-0 bottom-0 h-2/3 md:h-1/2 bg-gray-900 rounded-t-3xl z-60"
            @click.outside="showComments = false"
        >
            <div class="h-full flex flex-col">
                <!-- Header -->
                <div class="flex-shrink-0 p-4 border-b border-gray-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-white font-medium">Comments <span class="text-gray-400" x-text="'(' + commentCount + ')'"></span></h3>
                        <button @click="showComments = false" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Comment Form -->
                @auth
                <div class="flex-shrink-0 p-4 border-b border-gray-800">
                    <form @submit.prevent="postComment()" class="flex gap-3">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                        @else
                            <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1">
                            <input 
                                type="text" 
                                x-model="newComment"
                                placeholder="Add a comment..." 
                                class="w-full bg-gray-800 border border-gray-700 rounded-full px-4 py-2 text-white text-sm placeholder-gray-400 focus:outline-none focus:border-gray-600"
                            >
                        </div>
                        <button 
                            type="submit"
                            :disabled="!newComment.trim() || postingComment"
                            class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span x-show="!postingComment">Post</span>
                            <span x-show="postingComment">...</span>
                        </button>
                    </form>
                    <p x-show="commentError" x-text="commentError" class="text-red-500 text-xs mt-2"></p>
                </div>
                @else
                <div class="flex-shrink-0 p-4 border-b border-gray-800 text-center">
                    <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300">Sign in</a>
                    <span class="text-gray-400"> to comment</span>
                </div>
                @endauth

                <!-- Comments List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <!-- Loading state -->
                    <div x-show="loadingComments" class="flex justify-center py-8">
                        <svg class="animate-spin h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- New comments (posted via AJAX) -->
                    <template x-for="comment in newComments" :key="comment.id">
                        <div class="flex gap-3">
                            <template x-if="comment.user.avatar_url">
                                <img :src="comment.user.avatar_url" :alt="comment.user.name" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                            </template>
                            <template x-if="!comment.user.avatar_url">
                                <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium flex-shrink-0" x-text="comment.user.name.charAt(0).toUpperCase()"></div>
                            </template>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-white text-sm font-medium" x-text="comment.user.name"></span>
                                    <span class="text-gray-500 text-xs">just now</span>
                                </div>
                                <p class="text-gray-300 text-sm mt-1" x-text="comment.body"></p>
                            </div>
                        </div>
                    </template>

                    <!-- Existing comments -->
                    <template x-for="comment in comments" :key="comment.id">
                        <div class="flex gap-3">
                            <a :href="'/channel/' + comment.user.username" class="flex-shrink-0">
                                <template x-if="comment.user.avatar_url">
                                    <img :src="comment.user.avatar_url" :alt="comment.user.name" class="w-8 h-8 rounded-full object-cover">
                                </template>
                                <template x-if="!comment.user.avatar_url">
                                    <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium" x-text="comment.user.name.charAt(0).toUpperCase()"></div>
                                </template>
                            </a>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <a :href="'/channel/' + comment.user.username" class="text-white text-sm font-medium hover:text-gray-300" x-text="comment.user.name"></a>
                                    <span class="text-gray-500 text-xs" x-text="comment.created_at_formatted"></span>
                                </div>
                                <p class="text-gray-300 text-sm mt-1" x-text="comment.body"></p>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div x-show="!loadingComments && comments.length === 0 && newComments.length === 0" class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-gray-400">No comments yet</p>
                        <p class="text-gray-500 text-sm">Be the first to comment!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share Modal -->
        <div 
            x-show="showShareModal" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/70 flex items-center justify-center z-60 p-4"
            @click.self="showShareModal = false"
        >
            <div class="bg-gray-900 rounded-2xl w-full max-w-md p-6" @click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-white font-medium text-lg">Share</h3>
                    <button @click="showShareModal = false" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Share buttons -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <button @click="shareToTwitter()" class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-gray-800 transition-colors">
                        <div class="w-12 h-12 bg-[#1DA1F2] rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </div>
                        <span class="text-gray-300 text-xs">Twitter</span>
                    </button>
                    <button @click="shareToFacebook()" class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-gray-800 transition-colors">
                        <div class="w-12 h-12 bg-[#1877F2] rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </div>
                        <span class="text-gray-300 text-xs">Facebook</span>
                    </button>
                    <button @click="shareToWhatsApp()" class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-gray-800 transition-colors">
                        <div class="w-12 h-12 bg-[#25D366] rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <span class="text-gray-300 text-xs">WhatsApp</span>
                    </button>
                    <button @click="shareToTelegram()" class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-gray-800 transition-colors">
                        <div class="w-12 h-12 bg-[#0088CC] rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                        </div>
                        <span class="text-gray-300 text-xs">Telegram</span>
                    </button>
                </div>

                <!-- Copy link -->
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        :value="shareUrl" 
                        readonly
                        class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-gray-300 text-sm"
                    >
                    <button 
                        @click="copyLink()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                    >
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function shortsPlayer() {
            return {
                playing: false,
                muted: false,
                showPlayIcon: true,
                showComments: false,
                showShareModal: false,
                likeCount: {{ $video->likes_count ?? 0 }},
                dislikeCount: {{ $video->dislikes_count ?? 0 }},
                userReaction: '{{ auth()->check() ? ($video->reactions->where("user_id", auth()->id())->first()?->reaction ?? "") : "" }}',
                subscribed: {{ auth()->check() && auth()->user()->isSubscribedTo($video->user) ? 'true' : 'false' }},
                moreShorts: @json($moreShorts->pluck('slug')),
                currentIndex: 0,
                // Comments
                comments: [],
                newComments: [],
                newComment: '',
                commentCount: {{ $video->comments_count ?? 0 }},
                loadingComments: false,
                postingComment: false,
                commentError: '',
                commentsLoaded: false,
                // Share
                shareUrl: window.location.href,
                copied: false,

                init() {
                    // Auto-play on load
                    this.$nextTick(() => {
                        this.$refs.video.play().then(() => {
                            this.playing = true;
                            this.showPlayIcon = false;
                        }).catch(() => {
                            // Autoplay blocked, show play button
                            this.showPlayIcon = true;
                        });
                    });

                    // Watch for comments panel opening
                    this.$watch('showComments', (value) => {
                        if (value && !this.commentsLoaded) {
                            this.loadComments();
                        }
                    });
                },

                togglePlay() {
                    if (this.$refs.video.paused) {
                        this.$refs.video.play();
                        this.playing = true;
                        this.showPlayIcon = false;
                    } else {
                        this.$refs.video.pause();
                        this.playing = false;
                        this.showPlayIcon = true;
                    }
                },

                toggleMute() {
                    this.muted = !this.muted;
                    this.$refs.video.muted = this.muted;
                },

                onEnded() {
                    // Auto-replay (loop is already set)
                },

                nextShort() {
                    if (this.moreShorts.length > this.currentIndex) {
                        window.location.href = '/shorts/' + this.moreShorts[this.currentIndex];
                    }
                },

                async react(type) {
                    @auth
                    try {
                        const response = await fetch('{{ route("video.react", $video) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ reaction: type })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.likeCount = data.likes_count;
                            this.dislikeCount = data.dislikes_count;
                            // If action is 'removed', clear reaction; otherwise set the new reaction
                            this.userReaction = data.action === 'removed' ? '' : data.reaction;
                        }
                    } catch (e) {
                        console.error('Reaction failed:', e);
                    }
                    @else
                    window.location.href = '{{ route("login") }}';
                    @endauth
                },

                async subscribe() {
                    @auth
                    try {
                        const response = await fetch('{{ route("channel.subscribe", $video->user->username) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.subscribed = data.subscribed;
                        }
                    } catch (e) {
                        console.error('Subscribe failed:', e);
                    }
                    @else
                    window.location.href = '{{ route("login") }}';
                    @endauth
                },

                shareVideo() {
                    this.showShareModal = true;
                },

                shareToTwitter() {
                    const text = encodeURIComponent('{{ $video->title }}');
                    const url = encodeURIComponent(this.shareUrl);
                    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'width=550,height=420');
                },

                shareToFacebook() {
                    const url = encodeURIComponent(this.shareUrl);
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=550,height=420');
                },

                shareToWhatsApp() {
                    const text = encodeURIComponent('{{ $video->title }} ' + this.shareUrl);
                    window.open(`https://wa.me/?text=${text}`, '_blank');
                },

                shareToTelegram() {
                    const text = encodeURIComponent('{{ $video->title }}');
                    const url = encodeURIComponent(this.shareUrl);
                    window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
                },

                async copyLink() {
                    try {
                        await navigator.clipboard.writeText(this.shareUrl);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    } catch (e) {
                        console.error('Failed to copy:', e);
                    }
                },

                async loadComments() {
                    this.loadingComments = true;
                    try {
                        const response = await fetch('{{ route("video.comments", $video) }}', {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        this.comments = data.comments || [];
                        this.commentCount = data.total || this.comments.length;
                        this.commentsLoaded = true;
                    } catch (e) {
                        console.error('Failed to load comments:', e);
                    } finally {
                        this.loadingComments = false;
                    }
                },

                async postComment() {
                    if (!this.newComment.trim()) return;
                    
                    this.postingComment = true;
                    this.commentError = '';
                    
                    try {
                        const response = await fetch('{{ route("video.comment", $video) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ body: this.newComment })
                        });
                        
                        const data = await response.json();
                        
                        if (response.ok && data.comment) {
                            this.newComments.unshift(data.comment);
                            this.newComment = '';
                            this.commentCount++;
                        } else {
                            this.commentError = data.message || 'Failed to post comment';
                        }
                    } catch (e) {
                        console.error('Failed to post comment:', e);
                        this.commentError = 'Failed to post comment';
                    } finally {
                        this.postingComment = false;
                    }
                },

                formatCount(num) {
                    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
                    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
                    return num.toString();
                }
            }
        }
    </script>
    @endpush
</x-main-layout>
