<x-main-layout>
    <x-slot name="title">{{ $video->title }} - {{ config('app.name') }}</x-slot>

    <div class="max-w-[1800px] mx-auto" x-data="videoPage()" x-init="init()">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Main Content -->
            <div class="flex-1 min-w-0">
                {{-- Processing Banner --}}
                @if(($isOwner || $isAdmin) && $video->processing_state === 'pending')
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3 mb-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <p class="text-blue-400 text-sm">Optimizing for better playback... Video is already published.</p>
                    </div>
                </div>
                @endif

                <!-- Video Player -->
                <div class="relative aspect-video bg-black rounded-xl overflow-hidden mb-4" id="video-player-container">
                    @if($video->original_path)
                        <video 
                            id="video-player"
                            class="w-full h-full"
                            controls
                            playsinline
                            preload="metadata"
                            poster="{{ $video->thumbnail_url }}"
                            data-video-id="{{ $video->id }}"
                            controlslist="nodownload noremoteplayback"
                            disablepictureinpicture
                            oncontextmenu="return false;"
                        >
                            <source src="{{ $video->stream_url }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-900">
                            <div class="text-center">
                                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-gray-400">Video not available</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Video Info -->
                <div class="mb-4">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ $video->title }}</h1>
                    
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <!-- Views & Date -->
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ number_format($video->views_count ?? $video->views()->count()) }} views</span>
                            <span class="mx-2">•</span>
                            <span>{{ $video->created_at->format('M d, Y') }}</span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 flex-wrap">
                            @auth
                                <!-- Like/Dislike -->
                                <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-full" id="reaction-buttons">
                                    <button 
                                        type="button"
                                        @click="react('like')"
                                        :class="{ 'text-blue-500': userReaction === 'like', 'text-gray-700 dark:text-gray-300': userReaction !== 'like' }"
                                        class="flex items-center gap-2 px-4 py-2 rounded-l-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                        :disabled="reacting"
                                    >
                                        <svg class="w-5 h-5" :fill="userReaction === 'like' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                        </svg>
                                        <span class="text-sm font-medium" x-text="formatNumber(likesCount)">{{ number_format($video->likes_count ?? 0) }}</span>
                                    </button>
                                    <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>
                                    <button 
                                        type="button"
                                        @click="react('dislike')"
                                        :class="{ 'text-blue-500': userReaction === 'dislike', 'text-gray-700 dark:text-gray-300': userReaction !== 'dislike' }"
                                        class="flex items-center px-4 py-2 rounded-r-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                        :disabled="reacting"
                                    >
                                        <svg class="w-5 h-5" :fill="userReaction === 'dislike' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Share -->
                                <button onclick="shareVideo()" class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                    </svg>
                                    <span class="text-sm font-medium">Share</span>
                                </button>

                                <!-- Save (Watch Later) - AJAX -->
                                <button 
                                    type="button" 
                                    @click="toggleWatchLater()"
                                    :disabled="watchLaterLoading"
                                    class="flex items-center gap-2 px-4 py-2 rounded-full text-gray-700 dark:text-gray-300 transition-colors"
                                    :class="inWatchLater ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                >
                                    <svg class="w-5 h-5" :fill="inWatchLater ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                    <span class="text-sm font-medium hidden sm:inline" x-text="watchLaterLoading ? 'Saving...' : (inWatchLater ? 'Saved' : 'Save')"></span>
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-medium transition-colors">
                                    Sign in to like
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>

                <!-- Channel Info & Description -->
                <div class="bg-gray-100 dark:bg-gray-800/50 rounded-xl p-4 mb-6">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('channel.show', $video->user->username) }}">
                                @if($video->user->avatar)
                                    <img src="{{ $video->user->avatar_url }}" alt="{{ $video->user->name }}" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white font-medium">
                                        {{ strtoupper(substr($video->user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </a>
                            <div>
                                <a href="{{ route('channel.show', $video->user->username) }}" class="font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    {{ $video->user->name }}
                                </a>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($video->user->subscribers()->count()) }} subscribers</p>
                            </div>
                        </div>

                        @auth
                            @if(auth()->id() !== $video->user_id)
                                <form action="{{ route('channel.subscribe', $video->user->username) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $isSubscribed ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' : 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-200' }}">
                                        {{ $isSubscribed ? 'Subscribed' : 'Subscribe' }}
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-full text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-200 transition-colors">
                                Subscribe
                            </a>
                        @endauth
                    </div>

                    <!-- Description -->
                    <div x-data="{ expanded: false }">
                        <div class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap" :class="{ 'line-clamp-3': !expanded }">{{ $video->description }}</div>
                        @if(strlen($video->description) > 200)
                            <button @click="expanded = !expanded" class="text-gray-500 dark:text-gray-400 text-sm mt-2 font-medium hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                <span x-show="!expanded">Show more</span>
                                <span x-show="expanded">Show less</span>
                            </button>
                        @endif
                    </div>

                    <!-- Tags -->
                    @if($video->tags->count() > 0)
                        <div class="flex flex-wrap gap-2 mt-4">
                            @foreach($video->tags as $tag)
                                <a href="{{ route('search', ['q' => $tag->name]) }}" class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs text-blue-600 dark:text-blue-400 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Comments Section -->
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ number_format($video->comments_count ?? $video->comments()->whereNull('parent_id')->count()) }} Comments</h2>

                    @auth
                        <form @submit.prevent="postComment()" class="mb-6">
                            <div class="flex gap-3">
                                @if(auth()->user()->avatar)
                                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white font-medium flex-shrink-0">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <textarea 
                                        x-model="newComment"
                                        rows="2" 
                                        placeholder="Add a comment..." 
                                        class="w-full px-3 py-2 bg-transparent border-b border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-gray-500 dark:focus:border-gray-500 resize-none"
                                        required
                                    ></textarea>
                                    <div x-show="commentError" class="text-red-500 text-sm mt-1" x-text="commentError"></div>
                                    <div class="flex justify-end mt-2 gap-2">
                                        <button type="button" @click="newComment = ''; commentError = ''" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="postingComment || !newComment.trim()"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <span x-text="postingComment ? 'Posting...' : 'Comment'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @else
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Sign in</a> to leave a comment.
                        </p>
                    @endauth

                    <!-- Comments List -->
                    <div id="comments-container" class="space-y-4">
                        <!-- New comments will be prepended here via AJAX -->
                        <template x-for="comment in newComments" :key="comment.id">
                            <div class="flex gap-3">
                                <a :href="'/channel/' + comment.user.username" class="flex-shrink-0">
                                    <template x-if="comment.user.avatar_url">
                                        <img :src="comment.user.avatar_url" :alt="comment.user.name" class="w-10 h-10 rounded-full object-cover">
                                    </template>
                                    <template x-if="!comment.user.avatar_url">
                                        <div class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white font-medium" x-text="comment.user.name.charAt(0).toUpperCase()"></div>
                                    </template>
                                </a>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <a :href="'/channel/' + comment.user.username" class="text-sm font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors" x-text="comment.user.name"></a>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">just now</span>
                                    </div>
                                    <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap break-words" x-text="comment.body"></p>
                                </div>
                            </div>
                        </template>
                        @foreach($comments as $comment)
                            @include('components.comment', ['comment' => $comment])
                        @endforeach
                    </div>

                    @if($comments->hasMorePages())
                        <div class="mt-4">
                            {{ $comments->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar - Related Videos -->
            <div class="w-full lg:w-[400px] xl:w-[420px] flex-shrink-0">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Related Videos</h3>
                <div class="space-y-3">
                    @foreach($relatedVideos as $related)
                        <a href="{{ route('video.watch', $related->slug) }}" class="flex gap-2 group">
                            <div class="relative w-40 aspect-video bg-gray-200 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                                @if($related->has_thumbnail)
                                    <img src="{{ $related->thumbnail_url }}" alt="{{ $related->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                @if($related->duration)
                                    <div class="absolute bottom-1 right-1 px-1 py-0.5 bg-black/80 text-white rounded text-xs font-medium">
                                        {{ $related->formatted_duration }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">{{ $related->title }}</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $related->user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">{{ number_format($related->views_count ?? 0) }} views • {{ $related->created_at->diffForHumans() }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function videoPage() {
            return {
                userReaction: @json($userReaction?->reaction ?? null),
                likesCount: {{ $video->likes_count ?? 0 }},
                dislikesCount: {{ $video->dislikes_count ?? 0 }},
                reacting: false,
                viewRecorded: false,
                csrfToken: '{{ csrf_token() }}',
                
                // Watch Later state
                inWatchLater: {{ $inWatchLater ? 'true' : 'false' }},
                watchLaterLoading: false,
                
                // Comments state
                newComment: '',
                newComments: [],
                postingComment: false,
                commentError: '',
                commentsCount: {{ $video->comments_count ?? 0 }},
                
                init() {
                    this.initVideoPlayer();
                    this.initViewTracking();
                },
                
                initVideoPlayer() {
                    const video = document.getElementById('video-player');
                    if (!video) return;
                    
                    video.addEventListener('loadedmetadata', () => {
                        console.log('Video ready: duration =', video.duration);
                    });
                    
                    video.addEventListener('error', (e) => {
                        console.error('Video error:', e);
                    });
                },
                
                initViewTracking() {
                    const video = document.getElementById('video-player');
                    if (!video) return;
                    
                    video.addEventListener('timeupdate', () => {
                        if (!this.viewRecorded && video.currentTime >= 30) {
                            this.viewRecorded = true;
                            this.recordView();
                        }
                    });
                },
                
                async recordView() {
                    try {
                        await fetch('/api/v1/videos/{{ $video->id }}/view', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            }
                        });
                    } catch (e) {
                        console.log('View tracking failed:', e);
                    }
                },
                
                async react(type) {
                    if (this.reacting) return;
                    this.reacting = true;
                    
                    try {
                        const response = await fetch('{{ route('video.react', $video) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
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
                        console.error('Reaction failed:', e);
                    } finally {
                        this.reacting = false;
                    }
                },
                
                async toggleWatchLater() {
                    if (this.watchLaterLoading) return;
                    this.watchLaterLoading = true;
                    
                    try {
                        const response = await fetch('{{ route('video.watch-later', $video) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.inWatchLater = data.added;
                        }
                    } catch (e) {
                        console.error('Watch Later toggle failed:', e);
                    } finally {
                        this.watchLaterLoading = false;
                    }
                },
                
                async postComment() {
                    if (this.postingComment || !this.newComment.trim()) return;
                    this.postingComment = true;
                    this.commentError = '';
                    
                    try {
                        const response = await fetch('{{ route('video.comment', $video) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ body: this.newComment })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Add the new comment to the top of the list
                            this.newComments.unshift(data.comment);
                            this.newComment = '';
                            this.commentsCount++;
                        } else if (data.errors) {
                            this.commentError = Object.values(data.errors).flat()[0];
                        }
                    } catch (e) {
                        console.error('Comment post failed:', e);
                        this.commentError = 'Failed to post comment. Please try again.';
                    } finally {
                        this.postingComment = false;
                    }
                },
                
                formatNumber(num) {
                    if (num >= 1000000) {
                        return (num / 1000000).toFixed(1) + 'M';
                    } else if (num >= 1000) {
                        return (num / 1000).toFixed(1) + 'K';
                    }
                    return num.toLocaleString();
                }
            };
        }

        function shareVideo() {
            if (navigator.share) {
                navigator.share({
                    title: '{{ $video->title }}',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        }
    </script>
    @endpush
</x-main-layout>