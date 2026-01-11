<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $video->title }} - Shorts</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
        
        /* Safe area padding */
        .pt-safe { padding-top: env(safe-area-inset-top); }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        
        /* Action button tap area */
        .action-btn {
            min-width: 44px;
            min-height: 44px;
        }
        
        /* Shorts stage container - simple approach */
        .shorts-container {
            display: grid;
            place-items: center;
            height: 100dvh;
            padding-top: 56px; /* header height */
            padding-bottom: 16px;
        }
        
        /* The actual video stage */
        .shorts-stage {
            position: relative;
            width: min(420px, calc((100dvh - 72px) * 9 / 16), 100vw - 32px);
            aspect-ratio: 9 / 16;
            max-height: calc(100dvh - 72px);
        }
    </style>
</head>
<body class="bg-black text-white overflow-hidden">
    <!-- SHORTS PAGE: Full fixed viewport with grid centering -->
    <main x-data="shortsPlayer()" x-init="initPlayer()" class="fixed inset-0 bg-black text-white">
        
        <!-- Header overlay (absolute, stays on top) -->
        <header class="absolute inset-x-0 top-0 z-50 h-14 flex items-center justify-center pt-safe">
            <div class="w-full flex items-center justify-between px-4">
                <!-- Close button -->
                <a href="{{ route('shorts.index') }}" 
                   class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors"
                   aria-label="Close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
                
                <!-- Shorts logo -->
                <div class="flex items-center gap-1">
                    <svg class="w-7 h-7 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.77 10.32l-1.2-.5L18 9.06a3.74 3.74 0 10-3.5-6.62l-6 3.46a3.74 3.74 0 00-.1 6.5l1.2.5-1.4.75a3.74 3.74 0 103.5 6.62l6-3.46a3.74 3.74 0 00.07-6.49zM10 14.65l-1-1.73L12 11l1 1.73-3 1.92z"/>
                    </svg>
                    <span class="font-bold text-lg">Shorts</span>
                </div>
                
                <!-- More options -->
                <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors"
                        aria-label="More options">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Center area: grid container for stage -->
        <div class="shorts-container">
            
            <!-- ===== SHORTS STAGE ===== -->
            <div class="shorts-stage outline outline-2 outline-red-500/50">
                
                <!-- Video wrapper - RELATIVE positioning for action rail -->
                <div class="relative w-full h-full rounded-xl md:rounded-2xl bg-black">
                    <video 
                        id="short-video-{{ $video->id }}"
                        x-ref="video"
                        class="absolute inset-0 w-full h-full object-cover rounded-xl md:rounded-2xl"
                        src="{{ route('video.stream', $video->uuid) }}"
                        poster="{{ $video->thumbnail_url }}"
                        playsinline
                        webkit-playsinline
                        loop
                        x-bind:muted="muted"
                        @click="togglePlay()"
                    ></video>

                    <!-- Tap to unmute hint -->
                    <div x-show="muted && showUnmuteHint" 
                         x-transition
                         @click="toggleMute()"
                         class="absolute top-20 left-1/2 -translate-x-1/2 z-30 px-4 py-2 bg-black/70 rounded-full text-sm text-white flex items-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                        Tap to unmute
                    </div>

                    <!-- Play/Pause indicator -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none" 
                         x-show="showPlayPause" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-75"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-75">
                        <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center">
                            <svg x-show="!playing" class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <svg x-show="playing" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Gradient overlay for text readability -->
                    <div class="absolute inset-x-0 bottom-0 h-48 pointer-events-none" 
                         style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);"></div>
                    
                    <!-- Metadata overlay - bottom left -->
                    <div class="absolute left-3 right-20 bottom-4 z-20">
                        <!-- Channel info -->
                        <div class="flex items-center gap-2 mb-3">
                            <a href="{{ route('channel.show', $video->user->username) }}" class="flex-shrink-0">
                                @if($video->user->avatar_path)
                                    <img src="{{ $video->user->avatar_url }}" 
                                         alt="{{ $video->user->name }}" 
                                         class="w-9 h-9 rounded-full object-cover ring-2 ring-white/30">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-bold ring-2 ring-white/30">
                                        {{ strtoupper(substr($video->user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </a>
                            <a href="{{ route('channel.show', $video->user->username) }}" 
                               class="text-white text-sm font-semibold hover:underline">
                                {{ '@' . $video->user->username }}
                            </a>
                            @auth
                                @if(auth()->id() !== $video->user_id)
                                    <button 
                                        @click.stop="subscribe()"
                                        class="ml-1 px-3 py-1 text-xs font-semibold rounded-full transition-all"
                                        :class="subscribed ? 'bg-white/20 text-white' : 'bg-red-600 text-white hover:bg-red-700'">
                                        <span x-text="subscribed ? 'Subscribed' : 'Subscribe'"></span>
                                    </button>
                                @endif
                            @endauth
                        </div>
                        
                        <!-- Title & description -->
                        <p class="text-white text-sm font-medium line-clamp-2 mb-2">{{ $video->title }}</p>
                        
                        <!-- Stats -->
                        <div class="flex items-center gap-3 text-xs text-white/70">
                            <span>{{ number_format($video->views_count) }} views</span>
                            <span>•</span>
                            <span>{{ $video->published_at?->diffForHumans() ?? $video->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- ACTION RAIL - Right side, vertically centered -->
                    <!-- Compact transparent buttons -->
                    <!-- ========================================== -->
                    <div class="absolute right-1 top-1/2 -translate-y-1/2 z-50 flex flex-col items-center gap-0.5">
                        <!-- Like -->
                        <button @click.stop="react('like')" 
                                class="flex flex-col items-center group"
                                aria-label="Like">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all group-active:scale-90"
                                 :class="userReaction === 'like' ? 'text-red-500' : 'text-white'">
                                <svg class="w-5 h-5" 
                                     :fill="userReaction === 'like' ? 'currentColor' : 'none'" 
                                     stroke="currentColor" 
                                     stroke-width="2" 
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                </svg>
                            </div>
                            <span class="text-[9px] text-white" x-text="formatCount(likeCount)"></span>
                        </button>

                        <!-- Dislike -->
                        <button @click.stop="react('dislike')" 
                                class="flex flex-col items-center group"
                                aria-label="Dislike">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all group-active:scale-90"
                                 :class="userReaction === 'dislike' ? 'text-blue-400' : 'text-white'">
                                <svg class="w-5 h-5" 
                                     :fill="userReaction === 'dislike' ? 'currentColor' : 'none'" 
                                     stroke="currentColor" 
                                     stroke-width="2" 
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                                </svg>
                            </div>
                            <span class="text-[9px] text-white" x-text="formatCount(dislikeCount)"></span>
                        </button>

                        <!-- Comments -->
                        <button @click.stop="showComments = true" 
                                class="flex flex-col items-center group"
                                aria-label="Comments">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all group-active:scale-90 text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <span class="text-[9px] text-white" x-text="formatCount(commentCount)"></span>
                        </button>

                        <!-- Share -->
                        <button @click.stop="shareVideo()" 
                                class="flex flex-col items-center group"
                                aria-label="Share">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all group-active:scale-90 text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                            </div>
                            <span class="text-[9px] text-white">Share</span>
                        </button>

                        <!-- Volume/Unmute toggle -->
                        <button @click.stop="toggleMute()" 
                                class="flex flex-col items-center group"
                                aria-label="Toggle sound">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all group-active:scale-90"
                                 :class="muted ? 'text-red-500' : 'text-white'">
                                <span x-show="muted" class="text-base">🔇</span>
                                <span x-show="!muted" class="text-base">🔊</span>
                            </div>
                            <span class="text-[9px] text-white" x-text="muted ? 'Unmute' : 'Mute'"></span>
                        </button>
                    </div>
                    <!-- END ACTION RAIL -->
                </div>
            </div>
            <!-- END STAGE -->
        </div>
        <!-- END CENTER AREA -->

        <!-- Navigation arrows for more shorts (desktop only) -->
        @if($moreShorts->count() > 0)
            <div class="hidden md:block">
                <button @click="nextShort()" 
                        class="fixed right-6 top-1/2 translate-y-16 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all hover:scale-110 z-40"
                        aria-label="Next short">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </button>
            </div>
        @endif

        <!-- Comments sheet/modal -->
        <div x-show="showComments" 
             x-cloak
             class="fixed inset-0 z-[60]"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/60" @click="showComments = false"></div>
            
            <!-- Sheet -->
            <div class="absolute inset-x-0 bottom-0 md:inset-auto md:left-1/2 md:top-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-lg"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full md:translate-y-0 md:opacity-0 md:scale-95"
                 x-transition:enter-end="translate-y-0 md:opacity-100 md:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0 md:opacity-100 md:scale-100"
                 x-transition:leave-end="translate-y-full md:translate-y-0 md:opacity-0 md:scale-95"
                 @click.stop>
                <div class="bg-gray-900 rounded-t-2xl md:rounded-2xl max-h-[70vh] md:max-h-[80vh] flex flex-col pb-safe">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-4 border-b border-white/10">
                        <h3 class="font-semibold">Comments <span class="text-white/60" x-text="'(' + commentCount + ')'"></span></h3>
                        <button @click="showComments = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Comment form -->
                    @auth
                    <div class="p-4 border-b border-white/10">
                        <form @submit.prevent="postComment()" class="flex gap-3">
                            @if(auth()->user()->avatar_path)
                                <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                            @else
                                <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <input type="text" 
                                   x-model="newComment"
                                   placeholder="Add a comment..." 
                                   class="flex-1 bg-white/10 border-0 rounded-full px-4 py-2 text-sm text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30">
                            <button type="submit"
                                    :disabled="!newComment.trim() || postingComment"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                Post
                            </button>
                        </form>
                    </div>
                    @else
                    <div class="p-4 border-b border-white/10 text-center text-sm">
                        <a href="{{ route('login') }}" class="text-blue-400 hover:underline">Sign in</a>
                        <span class="text-white/60"> to comment</span>
                    </div>
                    @endauth

                    <!-- Comments list -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4">
                        <div x-show="loadingComments" class="flex justify-center py-8">
                            <svg class="animate-spin h-6 w-6 text-white/50" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <!-- New comments -->
                        <template x-for="comment in newComments" :key="'new-'+comment.id">
                            <div class="flex gap-3">
                                <template x-if="comment.user.avatar_url">
                                    <img :src="comment.user.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                </template>
                                <template x-if="!comment.user.avatar_url">
                                    <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium flex-shrink-0" x-text="comment.user.name.charAt(0).toUpperCase()"></div>
                                </template>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="font-medium" x-text="comment.user.name"></span>
                                        <span class="text-white/50 text-xs">just now</span>
                                    </div>
                                    <p class="text-sm text-white/90 mt-1" x-text="comment.body"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Existing comments -->
                        <template x-for="comment in comments" :key="comment.id">
                            <div class="flex gap-3">
                                <a :href="'/channel/' + comment.user.username" class="flex-shrink-0">
                                    <template x-if="comment.user.avatar_url">
                                        <img :src="comment.user.avatar_url" class="w-8 h-8 rounded-full object-cover">
                                    </template>
                                    <template x-if="!comment.user.avatar_url">
                                        <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium" x-text="comment.user.name.charAt(0).toUpperCase()"></div>
                                    </template>
                                </a>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 text-sm">
                                        <a :href="'/channel/' + comment.user.username" class="font-medium hover:underline" x-text="comment.user.name"></a>
                                        <span class="text-white/50 text-xs" x-text="comment.created_at_formatted"></span>
                                    </div>
                                    <p class="text-sm text-white/90 mt-1" x-text="comment.body"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Empty state -->
                        <div x-show="!loadingComments && comments.length === 0 && newComments.length === 0" class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-white/30 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p class="text-white/50">No comments yet</p>
                            <p class="text-white/30 text-sm">Be the first to comment!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share modal -->
        <div x-show="showShareModal" 
             x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/60" @click="showShareModal = false"></div>
            
            <div class="relative bg-gray-900 rounded-2xl w-full max-w-md p-6"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 @click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg">Share</h3>
                    <button @click="showShareModal = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Share buttons -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <button @click="shareToTwitter()" class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-white/10">
                        <div class="w-12 h-12 bg-[#1DA1F2] rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-white/70">Twitter</span>
                    </button>
                    <button @click="shareToFacebook()" class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-white/10">
                        <div class="w-12 h-12 bg-[#1877F2] rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-white/70">Facebook</span>
                    </button>
                    <button @click="shareToWhatsApp()" class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-white/10">
                        <div class="w-12 h-12 bg-[#25D366] rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-white/70">WhatsApp</span>
                    </button>
                    <button @click="shareToTelegram()" class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-white/10">
                        <div class="w-12 h-12 bg-[#0088CC] rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-white/70">Telegram</span>
                    </button>
                </div>

                <!-- Copy link -->
                <div class="flex gap-2">
                    <input type="text" 
                           :value="shareUrl" 
                           readonly
                           class="flex-1 bg-white/10 border-0 rounded-lg px-4 py-2 text-sm text-white/80">
                    <button @click="copyLink()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast notification -->
        <div x-show="toastMessage" 
             x-cloak
             x-transition
             class="fixed bottom-20 left-1/2 -translate-x-1/2 z-[70] px-4 py-2 bg-white text-black rounded-lg text-sm font-medium shadow-lg">
            <span x-text="toastMessage"></span>
        </div>
    </main>

    <script>
        function shortsPlayer() {
            return {
                videoId: {{ $video->id }},
                playing: false,
                muted: true, // Always start muted for autoplay compliance
                volume: 1.0,
                showPlayPause: false,
                showUnmuteHint: true,
                showComments: false,
                showShareModal: false,
                toastMessage: '',
                
                likeCount: {{ $video->likes_count ?? 0 }},
                dislikeCount: {{ $video->dislikes_count ?? 0 }},
                userReaction: '{{ auth()->check() ? ($video->reactions->where("user_id", auth()->id())->first()?->reaction ?? "") : "" }}',
                subscribed: {{ auth()->check() && auth()->user()->isSubscribedTo($video->user) ? 'true' : 'false' }},
                
                moreShorts: @json($moreShorts->pluck('slug')),
                currentShortIndex: 0,
                
                comments: [],
                newComments: [],
                newComment: '',
                commentCount: {{ $video->comments_count ?? 0 }},
                loadingComments: false,
                postingComment: false,
                commentsLoaded: false,
                
                shareUrl: window.location.href,
                copied: false,

                initPlayer() {
                    console.log('ACTION RAIL MOUNTED', this.videoId);
                    
                    const video = document.getElementById('short-video-' + this.videoId);
                    if (!video) {
                        console.error('Video element not found:', 'short-video-' + this.videoId);
                        return;
                    }
                    
                    // Store ref
                    this.$refs.video = video;
                    
                    // Set initial state
                    video.muted = true;
                    video.volume = this.volume;
                    
                    // Autoplay muted (browser policy)
                    video.play().then(() => {
                        console.log('Autoplay started (muted)');
                        this.playing = true;
                    }).catch((e) => {
                        console.log('Autoplay failed:', e);
                        this.playing = false;
                    });

                    // Load comments when panel opens
                    this.$watch('showComments', (open) => {
                        if (open && !this.commentsLoaded) {
                            this.loadComments();
                        }
                    });
                    
                    // Hide unmute hint after 5 seconds
                    setTimeout(() => {
                        this.showUnmuteHint = false;
                    }, 5000);
                },

                getVideo() {
                    return document.getElementById('short-video-' + this.videoId);
                },

                togglePlay() {
                    const video = this.getVideo();
                    if (!video) return;
                    
                    if (video.paused) {
                        video.play();
                        this.playing = true;
                    } else {
                        video.pause();
                        this.playing = false;
                    }
                    // Show indicator briefly
                    this.showPlayPause = true;
                    setTimeout(() => this.showPlayPause = false, 500);
                },

                toggleMute() {
                    const video = this.getVideo();
                    if (!video) {
                        console.error('Video not found for unmute');
                        return;
                    }
                    
                    this.muted = !this.muted;
                    video.muted = this.muted;
                    video.volume = this.volume;
                    
                    console.log('UNMUTE CLICK', this.videoId, 'muted:', video.muted, 'volume:', video.volume);
                    
                    // Persist preference to localStorage
                    localStorage.setItem('shortsMuted', this.muted ? '1' : '0');
                    
                    // If unmuting, ensure video is playing with audio
                    if (!this.muted) {
                        this.showUnmuteHint = false;
                        video.play().catch(() => {});
                        this.playing = true;
                    }
                    
                    // Show feedback toast
                    this.showToast(this.muted ? 'Sound off' : 'Sound on');
                },

                nextShort() {
                    if (this.moreShorts.length > this.currentShortIndex) {
                        window.location.href = '/shorts/' + this.moreShorts[this.currentShortIndex];
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
                            this.userReaction = data.action === 'removed' ? '' : data.reaction;
                        }
                    } catch (e) {
                        console.error('Reaction failed:', e);
                    }
                    @else
                    this.showToast('Sign in to like videos');
                    setTimeout(() => {
                        window.location.href = '{{ route("login") }}?redirect=' + encodeURIComponent(window.location.href);
                    }, 1000);
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
                            this.showToast(data.subscribed ? 'Subscribed!' : 'Unsubscribed');
                        }
                    } catch (e) {
                        console.error('Subscribe failed:', e);
                    }
                    @else
                    this.showToast('Sign in to subscribe');
                    setTimeout(() => {
                        window.location.href = '{{ route("login") }}?redirect=' + encodeURIComponent(window.location.href);
                    }, 1000);
                    @endauth
                },

                shareVideo() {
                    // Try native share first (mobile)
                    if (navigator.share) {
                        navigator.share({
                            title: '{{ $video->title }}',
                            url: this.shareUrl
                        }).catch(() => {
                            // If cancelled or failed, show modal
                            this.showShareModal = true;
                        });
                    } else {
                        this.showShareModal = true;
                    }
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
                        this.showToast('Link copied!');
                    } catch (e) {
                        console.error('Failed to copy:', e);
                    }
                },

                showToast(message) {
                    this.toastMessage = message;
                    setTimeout(() => this.toastMessage = '', 2000);
                },

                async loadComments() {
                    this.loadingComments = true;
                    try {
                        const response = await fetch('{{ route("video.comments", $video) }}', {
                            headers: { 'Accept': 'application/json' }
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
                        }
                    } catch (e) {
                        console.error('Failed to post comment:', e);
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
</body>
</html>
