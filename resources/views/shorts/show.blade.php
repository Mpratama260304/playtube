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
        
        /* Reset */
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            background: #000;
        }
        
        /* Main scroll container - THE KEY ELEMENT */
        .shorts-feed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow-y: scroll;
            overflow-x: hidden;
            scroll-snap-type: y mandatory;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            /* Hide scrollbar */
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .shorts-feed::-webkit-scrollbar { display: none; }
        
        /* Each slide - MUST have fixed height */
        .shorts-slide {
            width: 100%;
            height: 100vh;
            height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            scroll-snap-align: start;
            scroll-snap-stop: always;
            flex-shrink: 0;
        }
        
        /* Video stage sizing */
        .shorts-stage {
            position: relative;
            width: min(420px, calc((100dvh - 56px) * 9 / 16), 100vw);
            max-height: calc(100dvh - 56px);
            aspect-ratio: 9 / 16;
        }
        
        @media (max-width: 640px) {
            .shorts-stage {
                width: 100vw;
                max-height: 100dvh;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-black text-white" x-data="shortsApp()" x-init="init()">
    
    <!-- Fixed Header - pointer-events-none allows scroll through -->
    <header class="fixed inset-x-0 top-0 z-50 h-14 bg-gradient-to-b from-black/80 to-transparent pt-safe" style="pointer-events: none;">
        <div class="w-full h-full flex items-center justify-between px-4" style="pointer-events: auto;">
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

    <!-- Scrollable Shorts Container - MAIN SCROLL AREA -->
    <main class="shorts-feed"
          @scroll="onScroll($event)"
          x-ref="scrollContainer">
        
            @foreach($allShorts as $index => $short)
            <div class="shorts-slide"
                 data-short-index="{{ $index }}"
                 data-short-id="{{ $short->id }}"
                 data-short-slug="{{ $short->slug }}">
                
                <!-- Video Stage -->
                <div class="shorts-stage relative rounded-xl md:rounded-2xl overflow-hidden bg-black"
                     x-data="shortItem({
                         id: {{ $short->id }},
                         slug: '{{ $short->slug }}',
                         uuid: '{{ $short->uuid }}',
                         title: {{ json_encode($short->title) }},
                         streamUrl: '{{ route('video.stream', $short->uuid) }}',
                         thumbnailUrl: '{{ $short->thumbnail_url }}',
                         viewsCount: {{ $short->views_count ?? 0 }},
                         likesCount: {{ $short->likes_count ?? 0 }},
                         dislikesCount: {{ $short->dislikes_count ?? 0 }},
                         commentsCount: {{ $short->comments_count ?? 0 }},
                         publishedAt: '{{ $short->published_at?->diffForHumans() ?? $short->created_at->diffForHumans() }}',
                         user: {
                             id: {{ $short->user->id }},
                             name: {{ json_encode($short->user->name) }},
                             username: '{{ $short->user->username }}',
                             avatarUrl: '{{ $short->user->avatar_url }}'
                         },
                         userReaction: '{{ auth()->check() ? ($short->reactions->where('user_id', auth()->id())->first()?->reaction ?? '') : '' }}',
                         isSubscribed: {{ auth()->check() && auth()->user()->isSubscribedTo($short->user) ? 'true' : 'false' }}
                     }, {{ $index }})"
                     x-init="initShort()">
                    
                    <!-- Video Element -->
                    <video 
                        x-ref="video"
                        class="absolute inset-0 w-full h-full object-cover"
                        :src="short.streamUrl"
                        :poster="short.thumbnailUrl"
                        preload="auto"
                        playsinline
                        webkit-playsinline
                        loop
                        :muted="$store.shorts.muted"
                        @click="togglePlay()"
                        @loadstart="loading = isActive"
                        @canplay="loading = false"
                        @playing="loading = false; playing = true"
                        @pause="playing = false"
                    ></video>
                    
                    <!-- Loading spinner -->
                    <div x-show="isActive && loading" x-cloak class="absolute inset-0 flex items-center justify-center bg-black/50">
                        <svg class="animate-spin h-10 w-10 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Tap to unmute hint -->
                    <div x-show="isActive && $store.shorts.muted && showUnmuteHint" 
                         x-cloak
                         x-transition
                         @click="toggleMute()"
                         class="absolute top-16 left-1/2 -translate-x-1/2 z-30 px-4 py-2 bg-black/70 rounded-full text-sm text-white flex items-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                        Tap to unmute
                    </div>

                    <!-- Play/Pause indicator -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none" 
                         x-show="showPlayPause"
                         x-cloak
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

                    <!-- Gradient overlay -->
                    <div class="absolute inset-x-0 bottom-0 h-48 pointer-events-none" 
                         style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);"></div>
                    
                    <!-- Metadata overlay - bottom left -->
                    <div class="absolute left-3 right-16 bottom-4 z-20">
                        <!-- Channel info -->
                        <div class="flex items-center gap-2 mb-2">
                            <a :href="'/channel/' + short.user.username" class="flex-shrink-0">
                                <template x-if="short.user.avatarUrl">
                                    <img :src="short.user.avatarUrl" 
                                         :alt="short.user.name" 
                                         class="w-8 h-8 rounded-full object-cover ring-2 ring-white/30">
                                </template>
                                <template x-if="!short.user.avatarUrl">
                                    <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-bold ring-2 ring-white/30"
                                         x-text="short.user.name.charAt(0).toUpperCase()"></div>
                                </template>
                            </a>
                            <a :href="'/channel/' + short.user.username" 
                               class="text-white text-sm font-semibold hover:underline"
                               x-text="'@' + short.user.username"></a>
                            @auth
                            <button 
                                @click.stop="subscribe()"
                                class="ml-1 px-3 py-1 text-xs font-semibold rounded-full transition-all"
                                :class="subscribed ? 'bg-white/20 text-white' : 'bg-red-600 text-white hover:bg-red-700'"
                                x-show="short.user.id !== {{ auth()->id() }}">
                                <span x-text="subscribed ? 'Subscribed' : 'Subscribe'"></span>
                            </button>
                            @endauth
                        </div>
                        
                        <!-- Title -->
                        <p class="text-white text-sm font-medium line-clamp-2 mb-1" x-text="short.title"></p>
                        
                        <!-- Stats -->
                        <div class="flex items-center gap-3 text-xs text-white/70">
                            <span x-text="formatCount(short.viewsCount) + ' views'"></span>
                            <span>•</span>
                            <span x-text="short.publishedAt"></span>
                        </div>
                    </div>

                    <!-- Action Rail - Right side -->
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 z-50 flex flex-col items-center gap-1">
                        <!-- Like -->
                        <button @click.stop="react('like')" class="flex flex-col items-center group">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center transition-all group-active:scale-90"
                                 :class="userReaction === 'like' ? 'text-red-500' : 'text-white'">
                                <svg class="w-6 h-6" 
                                     :fill="userReaction === 'like' ? 'currentColor' : 'none'" 
                                     stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                </svg>
                            </div>
                            <span class="text-[10px] text-white" x-text="formatCount(likeCount)"></span>
                        </button>

                        <!-- Dislike -->
                        <button @click.stop="react('dislike')" class="flex flex-col items-center group">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center transition-all group-active:scale-90"
                                 :class="userReaction === 'dislike' ? 'text-blue-400' : 'text-white'">
                                <svg class="w-6 h-6" 
                                     :fill="userReaction === 'dislike' ? 'currentColor' : 'none'" 
                                     stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                                </svg>
                            </div>
                            <span class="text-[10px] text-white" x-text="formatCount(dislikeCount)"></span>
                        </button>

                        <!-- Comments -->
                        <button @click.stop="openComments()" class="flex flex-col items-center group">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center transition-all group-active:scale-90 text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] text-white" x-text="formatCount(commentCount)"></span>
                        </button>

                        <!-- Share -->
                        <button @click.stop="shareVideo()" class="flex flex-col items-center group">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center transition-all group-active:scale-90 text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] text-white">Share</span>
                        </button>

                        <!-- Mute/Unmute -->
                        <button @click.stop="toggleMute()" class="flex flex-col items-center group">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center transition-all group-active:scale-90"
                                 :class="$store.shorts.muted ? 'text-red-500' : 'text-white'">
                                <span x-show="$store.shorts.muted" class="text-lg">🔇</span>
                                <span x-show="!$store.shorts.muted" class="text-lg">🔊</span>
                            </div>
                            <span class="text-[10px] text-white" x-text="$store.shorts.muted ? 'Unmute' : 'Mute'"></span>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
            
            <!-- Loading more indicator -->
            <div x-show="loadingMore" x-cloak class="shorts-slide">
                <svg class="animate-spin h-8 w-8 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
    </main>

    <!-- Comments Modal -->
    <div x-show="$store.shorts.showComments" 
         x-cloak
         x-data="commentsModal()"
         x-init="$watch('$store.shorts.showComments', (open) => { if (open) loadComments(); })"
         class="fixed inset-0 z-[60]"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/60" @click="$store.shorts.showComments = false"></div>
        
        <div class="absolute inset-x-0 bottom-0 md:inset-auto md:left-1/2 md:top-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-lg"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full md:translate-y-0 md:opacity-0 md:scale-95"
             x-transition:enter-end="translate-y-0 md:opacity-100 md:scale-100"
             @click.stop>
            <div class="bg-gray-900 rounded-t-2xl md:rounded-2xl max-h-[70vh] md:max-h-[80vh] flex flex-col pb-safe">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-white/10">
                    <h3 class="font-semibold">Comments <span class="text-white/50" x-text="'(' + totalComments + ')'"></span></h3>
                    <button @click="$store.shorts.showComments = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Comment Form -->
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
                               style="background-color: #374151; color: #ffffff;"
                               class="flex-1 border-0 rounded-full px-4 py-2 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit"
                                :disabled="!newComment.trim() || posting"
                                class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!posting">Post</span>
                            <span x-show="posting">...</span>
                        </button>
                    </form>
                </div>
                @else
                <div class="p-4 border-b border-white/10 text-center text-sm">
                    <a href="{{ route('login') }}" class="text-blue-400 hover:underline">Sign in</a>
                    <span class="text-white/60"> to comment</span>
                </div>
                @endauth
                
                <!-- Comments List -->
                <div class="flex-1 overflow-y-auto p-4 min-h-[200px] space-y-4">
                    <!-- Loading state -->
                    <div x-show="loading" class="flex justify-center py-8">
                        <svg class="animate-spin h-6 w-6 text-white/50" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <!-- Comments -->
                    <template x-for="comment in comments" :key="comment.id">
                        <div class="flex gap-3">
                            <a :href="'/channel/' + comment.user.username" class="flex-shrink-0">
                                <template x-if="comment.user.avatar_url">
                                    <img :src="comment.user.avatar_url" class="w-8 h-8 rounded-full object-cover">
                                </template>
                                <template x-if="!comment.user.avatar_url">
                                    <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white text-sm font-medium" 
                                         x-text="comment.user.name.charAt(0).toUpperCase()"></div>
                                </template>
                            </a>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 text-sm">
                                    <a :href="'/channel/' + comment.user.username" class="font-medium hover:underline" x-text="comment.user.name"></a>
                                    <span class="text-white/50 text-xs" x-text="comment.created_at_formatted || comment.created_at"></span>
                                </div>
                                <p class="text-sm text-white/90 mt-1" x-text="comment.body"></p>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Empty state -->
                    <div x-show="!loading && comments.length === 0" class="text-center py-8">
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

    <!-- Share Modal -->
    <div x-show="$store.shorts.showShare" 
         x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/60" @click="$store.shorts.showShare = false"></div>
        
        <div class="relative bg-gray-900 rounded-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">Share</h3>
                <button @click="$store.shorts.showShare = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10">
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
                       x-model="$store.shorts.shareUrl" 
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
    <div x-show="$store.shorts.toast" 
         x-cloak
         x-transition
         class="fixed bottom-20 left-1/2 -translate-x-1/2 z-[70] px-4 py-2 bg-white text-black rounded-lg text-sm font-medium shadow-lg">
        <span x-text="$store.shorts.toast"></span>
    </div>

    <script>
        // Global store for shared state
        document.addEventListener('alpine:init', () => {
            Alpine.store('shorts', {
                muted: true,
                activeIndex: 0,
                showComments: false,
                showShare: false,
                shareUrl: window.location.href,
                toast: '',
                
                showToast(message) {
                    this.toast = message;
                    setTimeout(() => this.toast = '', 2000);
                }
            });
        });

        // Main app controller
        function shortsApp() {
            return {
                loadedIds: @json($allShorts->pluck('id')),
                loadingMore: false,
                hasMore: {{ $allShorts->count() >= 10 ? 'true' : 'false' }},
                copied: false,
                
                init() {
                    // Handle keyboard navigation
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowDown' || e.key === 'j') {
                            e.preventDefault();
                            this.scrollToNext();
                        } else if (e.key === 'ArrowUp' || e.key === 'k') {
                            e.preventDefault();
                            this.scrollToPrev();
                        } else if (e.key === ' ') {
                            e.preventDefault();
                            // Toggle play on space - handled by active short
                        }
                    });
                    
                    // Touch/swipe support is handled by native scroll
                },
                
                onScroll(event) {
                    const container = event.target;
                    const scrollTop = container.scrollTop;
                    const slideHeight = window.innerHeight;
                    const newIndex = Math.round(scrollTop / slideHeight);
                    
                    if (newIndex !== Alpine.store('shorts').activeIndex) {
                        Alpine.store('shorts').activeIndex = newIndex;
                        this.updateUrl(newIndex);
                    }
                    
                    // Load more when near bottom
                    if (this.hasMore && !this.loadingMore) {
                        const scrollBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
                        if (scrollBottom < slideHeight * 2) {
                            this.loadMore();
                        }
                    }
                },
                
                updateUrl(index) {
                    const slide = document.querySelector(`[data-short-index="${index}"]`);
                    if (slide) {
                        const slug = slide.dataset.shortSlug;
                        const newUrl = '/shorts/' + slug;
                        if (window.location.pathname !== newUrl) {
                            history.replaceState({}, '', newUrl);
                            Alpine.store('shorts').shareUrl = window.location.origin + newUrl;
                        }
                    }
                },
                
                scrollToNext() {
                    const container = this.$refs.scrollContainer;
                    const currentIndex = Alpine.store('shorts').activeIndex;
                    const totalSlides = document.querySelectorAll('.shorts-slide').length;
                    if (currentIndex < totalSlides - 1) {
                        container.scrollTo({
                            top: (currentIndex + 1) * window.innerHeight,
                            behavior: 'smooth'
                        });
                    }
                },
                
                scrollToPrev() {
                    const container = this.$refs.scrollContainer;
                    const currentIndex = Alpine.store('shorts').activeIndex;
                    if (currentIndex > 0) {
                        container.scrollTo({
                            top: (currentIndex - 1) * window.innerHeight,
                            behavior: 'smooth'
                        });
                    }
                },
                
                async loadMore() {
                    if (this.loadingMore || !this.hasMore) return;
                    
                    this.loadingMore = true;
                    try {
                        const response = await fetch('/shorts/load-more', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ exclude: this.loadedIds })
                        });
                        
                        const data = await response.json();
                        
                        if (data.shorts && data.shorts.length > 0) {
                            // Append new shorts to DOM
                            const container = document.querySelector('.shorts-scroll-container > div');
                            const loadingIndicator = container.querySelector('[x-show="loadingMore"]').parentElement;
                            
                            data.shorts.forEach((short, idx) => {
                                this.loadedIds.push(short.id);
                                const newIndex = document.querySelectorAll('.shorts-slide').length;
                                
                                // Create new slide element
                                const slideHtml = this.createSlideHtml(short, newIndex);
                                loadingIndicator.insertAdjacentHTML('beforebegin', slideHtml);
                            });
                            
                            // Reinitialize Alpine components
                            this.$nextTick(() => {
                                document.querySelectorAll('.shorts-slide:not([data-initialized])').forEach(slide => {
                                    slide.setAttribute('data-initialized', 'true');
                                });
                            });
                        }
                        
                        this.hasMore = data.has_more;
                    } catch (e) {
                        console.error('Failed to load more shorts:', e);
                    } finally {
                        this.loadingMore = false;
                    }
                },
                
                createSlideHtml(short, index) {
                    // This is a simplified version - the actual implementation would need full HTML
                    // For now, we rely on server-side rendering of initial shorts
                    return '';
                },
                
                // Share functions
                shareToTwitter() {
                    const text = encodeURIComponent(document.title);
                    const url = encodeURIComponent(Alpine.store('shorts').shareUrl);
                    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'width=550,height=420');
                },

                shareToFacebook() {
                    const url = encodeURIComponent(Alpine.store('shorts').shareUrl);
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=550,height=420');
                },

                shareToWhatsApp() {
                    const text = encodeURIComponent(document.title + ' ' + Alpine.store('shorts').shareUrl);
                    window.open(`https://wa.me/?text=${text}`, '_blank');
                },

                shareToTelegram() {
                    const text = encodeURIComponent(document.title);
                    const url = encodeURIComponent(Alpine.store('shorts').shareUrl);
                    window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
                },

                async copyLink() {
                    try {
                        await navigator.clipboard.writeText(Alpine.store('shorts').shareUrl);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                        Alpine.store('shorts').showToast('Link copied!');
                    } catch (e) {
                        console.error('Failed to copy:', e);
                    }
                }
            };
        }

        // Comments modal controller
        function commentsModal() {
            return {
                comments: [],
                newComment: '',
                loading: false,
                posting: false,
                totalComments: 0,
                loaded: false,
                
                async loadComments() {
                    // Get current active video slug
                    const activeIndex = Alpine.store('shorts').activeIndex;
                    const activeSlide = document.querySelector(`[data-short-index="${activeIndex}"]`);
                    if (!activeSlide) return;
                    
                    const videoSlug = activeSlide.dataset.shortSlug;
                    if (!videoSlug) return;
                    
                    // Reset and load
                    this.loading = true;
                    this.comments = [];
                    
                    try {
                        const response = await fetch(`/video/${videoSlug}/comments`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            this.comments = data.comments || [];
                            this.totalComments = data.total || this.comments.length;
                        }
                    } catch (e) {
                        console.error('Failed to load comments:', e);
                    } finally {
                        this.loading = false;
                        this.loaded = true;
                    }
                },
                
                async postComment() {
                    if (!this.newComment.trim() || this.posting) return;
                    
                    const activeIndex = Alpine.store('shorts').activeIndex;
                    const activeSlide = document.querySelector(`[data-short-index="${activeIndex}"]`);
                    if (!activeSlide) return;
                    
                    const videoSlug = activeSlide.dataset.shortSlug;
                    if (!videoSlug) return;
                    
                    this.posting = true;
                    
                    try {
                        const response = await fetch(`/video/${videoSlug}/comment`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ body: this.newComment })
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            if (data.comment) {
                                // Add new comment to the top
                                this.comments.unshift(data.comment);
                                this.totalComments++;
                                this.newComment = '';
                                Alpine.store('shorts').showToast('Comment posted!');
                            }
                        }
                    } catch (e) {
                        console.error('Failed to post comment:', e);
                        Alpine.store('shorts').showToast('Failed to post comment');
                    } finally {
                        this.posting = false;
                    }
                }
            };
        }

        // Individual short item controller
        function shortItem(shortData, index) {
            return {
                short: shortData,
                index: index,
                isActive: false,
                playing: false,
                loading: false,
                showPlayPause: false,
                showUnmuteHint: true,
                
                likeCount: shortData.likesCount || 0,
                dislikeCount: shortData.dislikesCount || 0,
                commentCount: shortData.commentsCount || 0,
                userReaction: shortData.userReaction || '',
                subscribed: shortData.isSubscribed || false,
                
                initShort() {
                    // Watch for active index changes
                    this.$watch('$store.shorts.activeIndex', (newIndex) => {
                        const wasActive = this.isActive;
                        this.isActive = (newIndex === this.index);
                        
                        if (this.isActive && !wasActive) {
                            // Use nextTick to ensure DOM is updated with new src
                            this.$nextTick(() => {
                                this.onBecomeActive();
                            });
                        } else if (!this.isActive && wasActive) {
                            this.onBecomeInactive();
                        }
                    });
                    
                    // Set initial active state for first short
                    if (this.index === 0) {
                        this.isActive = true;
                        this.$nextTick(() => this.onBecomeActive());
                    }
                    
                    // Hide unmute hint after delay
                    setTimeout(() => this.showUnmuteHint = false, 5000);
                },
                
                onBecomeActive() {
                    const video = this.$refs.video;
                    if (!video) {
                        console.log('Video ref not found for index:', this.index);
                        return;
                    }
                    
                    this.loading = true;
                    video.currentTime = 0;
                    video.muted = Alpine.store('shorts').muted;
                    
                    // Try to play - video should already have src loaded
                    const tryPlay = () => {
                        const playPromise = video.play();
                        if (playPromise !== undefined) {
                            playPromise.then(() => {
                                this.playing = true;
                                this.loading = false;
                            }).catch((e) => {
                                console.log('Autoplay prevented:', e);
                                this.playing = false;
                                this.loading = false;
                            });
                        }
                    };
                    
                    // If video is ready, play immediately, otherwise wait
                    if (video.readyState >= 2) {
                        tryPlay();
                    } else {
                        video.addEventListener('canplay', tryPlay, { once: true });
                    }
                },
                
                onBecomeInactive() {
                    const video = this.$refs.video;
                    if (video) {
                        video.pause();
                        video.currentTime = 0;
                    }
                    this.playing = false;
                },
                
                togglePlay() {
                    const video = this.$refs.video;
                    if (!video) return;
                    
                    if (video.paused) {
                        video.play();
                        this.playing = true;
                    } else {
                        video.pause();
                        this.playing = false;
                    }
                    
                    this.showPlayPause = true;
                    setTimeout(() => this.showPlayPause = false, 500);
                },
                
                toggleMute() {
                    Alpine.store('shorts').muted = !Alpine.store('shorts').muted;
                    this.showUnmuteHint = false;
                    
                    // Update all videos on the page
                    document.querySelectorAll('video').forEach(v => {
                        v.muted = Alpine.store('shorts').muted;
                    });
                    
                    const video = this.$refs.video;
                    if (video && !Alpine.store('shorts').muted) {
                        video.volume = 1;
                        video.play().catch(() => {});
                    }
                    
                    Alpine.store('shorts').showToast(Alpine.store('shorts').muted ? 'Sound off' : 'Sound on');
                },
                
                async react(type) {
                    @auth
                    try {
                        const response = await fetch('/video/' + this.short.id + '/react', {
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
                    Alpine.store('shorts').showToast('Sign in to like videos');
                    @endauth
                },
                
                async subscribe() {
                    @auth
                    try {
                        const response = await fetch('/channel/' + this.short.user.username + '/subscribe', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.subscribed = data.subscribed;
                            Alpine.store('shorts').showToast(data.subscribed ? 'Subscribed!' : 'Unsubscribed');
                        }
                    } catch (e) {
                        console.error('Subscribe failed:', e);
                    }
                    @else
                    Alpine.store('shorts').showToast('Sign in to subscribe');
                    @endauth
                },
                
                shareVideo() {
                    Alpine.store('shorts').shareUrl = window.location.origin + '/shorts/' + this.short.slug;
                    if (navigator.share) {
                        navigator.share({ 
                            title: this.short.title, 
                            url: Alpine.store('shorts').shareUrl 
                        }).catch(() => {
                            Alpine.store('shorts').showShare = true;
                        });
                    } else {
                        Alpine.store('shorts').showShare = true;
                    }
                },
                
                openComments() {
                    Alpine.store('shorts').showComments = true;
                },
                
                formatCount(num) {
                    if (!num) return '0';
                    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
                    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
                    return num.toString();
                }
            };
        }
    </script>
</body>
</html>
