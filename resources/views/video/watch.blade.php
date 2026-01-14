<x-main-layout>
    <x-slot name="title">{{ $video->title }} - {{ config('app.name') }}</x-slot>

    @section('og-tags')
    <meta property="og:type" content="video.other">
    <meta property="og:title" content="{{ $video->title }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($video->description), 200) ?: 'Watch on ' . config('app.name') }}">
    <meta property="og:image" content="{{ $video->thumbnail_url ? url($video->thumbnail_url) : url('/images/placeholder-thumb.svg') }}">
    <meta property="og:url" content="{{ route('video.watch', $video) }}">
    <meta property="og:site_name" content="{{ config('app.name', 'PlayTube') }}">
    <meta property="og:video:duration" content="{{ $video->duration ?? 0 }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $video->title }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($video->description), 200) ?: 'Watch on ' . config('app.name') }}">
    <meta name="twitter:image" content="{{ $video->thumbnail_url ? url($video->thumbnail_url) : url('/images/placeholder-thumb.svg') }}">
    @endsection

    {{-- Mobile-first layout with proper overflow handling --}}
    <div class="w-full max-w-[1800px] mx-auto px-0 sm:px-4 lg:px-6" x-data="window.videoPage()" x-init="init()">
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_400px] gap-3 lg:gap-6">
            <!-- Main Content -->
            <div class="min-w-0">
                {{-- Processing Banner --}}
                @if(($isOwner || $isAdmin) && $video->processing_state === 'pending')
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3 mb-3 mx-2 sm:mx-0">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-400 animate-spin flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <p class="text-blue-400 text-sm">Optimizing for better playback... Video is already published.</p>
                    </div>
                </div>
                @endif

                @if(($isOwner || $isAdmin) && !$video->stream_ready && $video->processing_state === 'processing')
                <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-3 mb-3 mx-2 sm:mx-0">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <p class="text-yellow-400 text-sm">Preparing fast-start stream... Playback will improve shortly.</p>
                    </div>
                </div>
                @endif

                <!-- Video Player Container - Full width on mobile -->
                <div class="relative w-full aspect-video bg-black sm:rounded-xl overflow-hidden mb-3" id="video-player-container">
                    @if($video->isEmbed())
                        {{-- Embedded Video Player --}}
                        <div class="relative w-full h-full">
                            {{-- Platform badge --}}
                            <div class="absolute top-2 left-2 z-10 px-2 py-1 bg-black/70 rounded text-xs text-white flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                {{ $video->embed_platform_name }}
                            </div>
                            <iframe
                                src="{{ $video->embed_iframe_url }}"
                                class="w-full h-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                        </div>
                    @elseif($video->original_path)
                        {{-- Loading Skeleton - shows until video can play --}}
                        <div 
                            x-show="!videoReady" 
                            x-transition:leave="transition-opacity duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute inset-0 z-20 flex items-center justify-center bg-black"
                        >
                            @if($video->thumbnail_url)
                            <img 
                                src="{{ $video->thumbnail_url }}" 
                                alt="" 
                                class="absolute inset-0 w-full h-full object-cover opacity-50"
                            >
                            @endif
                            <div class="relative z-10 flex flex-col items-center">
                                <div class="w-12 h-12 border-4 border-white/30 border-t-white rounded-full animate-spin"></div>
                                <p class="mt-3 text-white/70 text-sm" x-text="loadingText">Loading...</p>
                            </div>
                        </div>

                        <video 
                            id="video-player"
                            class="w-full h-full"
                            controls
                            playsinline
                            preload="metadata"
                            poster="{{ $video->thumbnail_url }}"
                            data-video-id="{{ $video->id }}"
                            data-stream-url="{{ $video->stream_url }}"
                            controlslist="nodownload"
                            oncontextmenu="return false;"
                            x-on:canplay="videoReady = true"
                            x-on:waiting="isBuffering = true"
                            x-on:playing="isBuffering = false; hasPlayed = true"
                            x-on:error="videoReady = true; loadingText = 'Error'"
                            x-ref="videoPlayer"
                        >
                            <source src="{{ $video->stream_url }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>

                        {{-- Buffering indicator (only shows during playback) --}}
                        <div 
                            x-show="isBuffering && videoReady" 
                            x-cloak
                            class="absolute inset-0 z-15 flex items-center justify-center bg-black/30 pointer-events-none"
                        >
                            <div class="w-10 h-10 border-4 border-white/30 border-t-white rounded-full animate-spin"></div>
                        </div>

                        <!-- Quality Selector Overlay -->
                        @if($video->hasRenditions() || $video->stream_ready)
                        <div 
                            class="absolute bottom-14 sm:bottom-16 right-2 sm:right-4 z-30" 
                            x-show="showQualityMenu" 
                            @click.away="showQualityMenu = false" 
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                        >
                            <div class="bg-black/95 rounded-lg shadow-xl overflow-hidden min-w-[130px]">
                                <div class="py-1">
                                    <button 
                                        @click="setQuality('auto')"
                                        class="w-full px-4 py-2.5 text-left text-sm hover:bg-white/10 transition-colors flex items-center justify-between"
                                        :class="selectedQuality === 'auto' ? 'text-blue-400 font-medium' : 'text-white'"
                                    >
                                        <span>Auto</span>
                                        <span x-show="selectedQuality === 'auto' && currentAutoQuality !== 'original'" class="text-xs opacity-75" x-text="currentAutoQuality + 'p'"></span>
                                    </button>
                                    @foreach($video->available_qualities ?? [] as $quality => $info)
                                    <button 
                                        @click="setQuality('{{ $quality }}')"
                                        class="w-full px-4 py-2.5 text-left text-sm hover:bg-white/10 transition-colors flex items-center justify-between"
                                        :class="selectedQuality === '{{ $quality }}' ? 'text-blue-400 font-medium' : 'text-white'"
                                    >
                                        <span>{{ $quality }}p</span>
                                        @if(isset($info['bitrate_kbps']))
                                        <span class="text-xs opacity-50">{{ round($info['bitrate_kbps'] / 1000, 1) }}M</span>
                                        @endif
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quality Button - Shows current quality -->
                        <button 
                            @click="showQualityMenu = !showQualityMenu"
                            class="absolute bottom-2 sm:bottom-4 right-2 sm:right-4 z-25 bg-black/80 hover:bg-black text-white px-2.5 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all"
                            title="Change quality"
                        >
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span x-text="getQualityLabel()"></span>
                            </span>
                        </button>
                        @endif
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-900">
                            <div class="text-center">
                                <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-gray-400">Video not available</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Video Info - Compact on mobile -->
                <div class="mb-3 px-3 sm:px-0">
                    <h1 class="text-base sm:text-lg lg:text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $video->title }}</h1>
                </div>

                <!-- Description Box (YouTube-style) - Below title -->
                <div class="bg-gray-100 dark:bg-gray-800/50 rounded-xl p-3 sm:p-4 mb-3 mx-2 sm:mx-0 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors" x-data="{ expanded: false }" @click="expanded = !expanded">
                    <!-- Collapsed View -->
                    <div x-show="!expanded">
                        <div class="flex items-center flex-wrap gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-300 font-medium">
                            <span>{{ number_format($video->views_count ?? $video->views()->count()) }} views</span>
                            <span>•</span>
                            <span>{{ $video->created_at->format('M j, Y') }}</span>
                            <span class="text-gray-900 dark:text-white font-semibold">...selengkapnya</span>
                        </div>
                    </div>
                    
                    <!-- Expanded View -->
                    <div x-show="expanded" x-collapse.duration.300ms>
                        <!-- Header Info -->
                        <div class="flex items-center flex-wrap gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-300 font-medium mb-3">
                            <span>{{ number_format($video->views_count ?? $video->views()->count()) }} views</span>
                            <span>•</span>
                            <span>{{ $video->created_at->format('M j, Y') }}</span>
                            @if($video->tags->count() > 0)
                                @foreach($video->tags->take(3) as $tag)
                                    <a href="{{ route('search', ['q' => $tag->name]) }}" @click.stop class="text-blue-600 dark:text-blue-400 hover:underline">#{{ $tag->name }}</a>
                                @endforeach
                            @endif
                        </div>
                        
                        <!-- Description Text -->
                        @if($video->description)
                        <div class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap break-words mb-3">{{ $video->description }}</div>
                        @else
                        <div class="text-gray-500 dark:text-gray-400 text-sm italic mb-3">No description</div>
                        @endif
                        
                        <!-- Tags & Category -->
                        @if($video->tags->count() > 3)
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            @foreach($video->tags->skip(3) as $tag)
                                <a href="{{ route('search', ['q' => $tag->name]) }}" @click.stop class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs text-blue-600 dark:text-blue-400 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                        @endif
                        
                        @if($video->category)
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Category: <a href="{{ route('category.show', $video->category->slug) }}" @click.stop class="text-blue-600 dark:text-blue-400 hover:underline">{{ $video->category->name }}</a>
                        </div>
                        @endif
                        
                        <!-- Collapse Button -->
                        <button @click.stop="expanded = false" class="text-gray-900 dark:text-white text-sm font-semibold hover:underline">
                            Tampilkan lebih sedikit
                        </button>
                    </div>
                </div>

                <!-- Actions - Horizontally scrollable on mobile -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide px-3 sm:px-0 sm:flex-wrap sm:overflow-visible mb-3">
                    @auth
                        <!-- Like/Dislike -->
                        <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-full flex-shrink-0" id="reaction-buttons">
                            <button 
                                type="button"
                                @click="react('like')"
                                :class="{ 'text-blue-500': userReaction === 'like', 'text-gray-700 dark:text-gray-300': userReaction !== 'like' }"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-l-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors min-h-[44px]"
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
                                class="flex items-center px-3 py-2 rounded-r-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors min-h-[44px]"
                                :disabled="reacting"
                            >
                                <svg class="w-5 h-5" :fill="userReaction === 'dislike' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Share -->
                        <button 
                            @click="openShareModal()"
                            class="flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex-shrink-0 min-h-[44px]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            <span class="text-sm font-medium">Share</span>
                        </button>

                        <!-- Save (Watch Later) -->
                        <button 
                            type="button" 
                            @click="toggleWatchLater()"
                            :disabled="watchLaterLoading"
                            class="flex items-center gap-1.5 px-4 py-2 rounded-full transition-colors flex-shrink-0 min-h-[44px]"
                            :class="inWatchLater ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        >
                            <svg class="w-5 h-5" :fill="inWatchLater ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            <span class="text-sm font-medium" x-text="watchLaterLoading ? '...' : (inWatchLater ? 'Saved' : 'Save')"></span>
                        </button>
                    @else
                        <!-- Share (Guest) -->
                        <button 
                            @click="openShareModal()"
                            class="flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex-shrink-0 min-h-[44px]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            <span class="text-sm font-medium">Share</span>
                        </button>
                        <a href="{{ route('login') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-medium transition-colors flex-shrink-0 min-h-[44px] flex items-center">
                            Sign in
                        </a>
                    @endauth
                </div>

                <!-- Channel Info -->
                <div class="bg-gray-100 dark:bg-gray-800/50 rounded-xl p-3 sm:p-4 mb-4 mx-2 sm:mx-0">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <a href="{{ route('channel.show', $video->user->username) }}" class="flex-shrink-0">
                                @if($video->user->avatar)
                                    <img src="{{ $video->user->avatar_url }}" alt="{{ $video->user->name }}" class="w-10 h-10 rounded-full object-cover" loading="lazy">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white font-medium">
                                        {{ strtoupper(substr($video->user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </a>
                            <div class="min-w-0">
                                <a href="{{ route('channel.show', $video->user->username) }}" class="font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors truncate block">
                                    {{ $video->user->name }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($video->user->subscribers()->count()) }} subscribers</p>
                            </div>
                        </div>

                        @auth
                            @if(auth()->id() !== $video->user_id)
                                <form action="{{ route('channel.subscribe', $video->user->username) }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 rounded-full text-sm font-medium transition-colors min-h-[40px] {{ $isSubscribed ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' : 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-200' }}">
                                        {{ $isSubscribed ? 'Subscribed' : 'Subscribe' }}
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-full text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-200 transition-colors flex-shrink-0 min-h-[40px] flex items-center">
                                Subscribe
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="mb-6 px-3 sm:px-0">
                    <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white mb-4">{{ number_format($video->comments_count ?? $video->comments()->whereNull('parent_id')->count()) }} Comments</h2>

                    @auth
                        <form @submit.prevent="postComment()" class="mb-6">
                            <div class="flex gap-3">
                                @if(auth()->user()->avatar)
                                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 sm:w-10 h-8 sm:h-10 rounded-full object-cover flex-shrink-0" loading="lazy">
                                @else
                                    <div class="w-8 sm:w-10 h-8 sm:h-10 rounded-full bg-brand-500 flex items-center justify-center text-white font-medium flex-shrink-0 text-sm">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <textarea 
                                        x-model="newComment"
                                        rows="2" 
                                        placeholder="Add a comment..." 
                                        class="w-full px-3 py-2 bg-transparent border-b border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-gray-500 dark:focus:border-gray-500 resize-none text-sm"
                                        required
                                    ></textarea>
                                    <div x-show="commentError" class="text-red-500 text-xs mt-1" x-text="commentError"></div>
                                    <div class="flex justify-end mt-2 gap-2">
                                        <button type="button" @click="newComment = ''; commentError = ''" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="postingComment || !newComment.trim()"
                                            class="px-4 py-1.5 bg-blue-600 text-white rounded-full text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <span x-text="postingComment ? 'Posting...' : 'Comment'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @else
                        <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">
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
            <div class="min-w-0 px-2 sm:px-0">
                <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white mb-3 px-1 sm:px-0">Related Videos</h3>
                <div class="space-y-2">
                    @foreach($relatedVideos as $related)
                        <a href="{{ route('video.watch', $related->slug) }}" @click="stopVideo()" class="flex gap-2 group hover:bg-gray-100 dark:hover:bg-gray-800/50 rounded-lg p-1 -mx-1 transition-colors">
                            <div class="relative w-36 sm:w-40 lg:w-44 aspect-video bg-gray-200 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                                @if($related->has_thumbnail)
                                    <img 
                                        src="{{ $related->thumbnail_url }}" 
                                        alt="{{ $related->title }}" 
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                @if($related->duration)
                                    <div class="absolute bottom-1 right-1 px-1 py-0.5 bg-black/80 text-white rounded text-[10px] font-medium">
                                        {{ $related->formatted_duration }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0 py-0.5">
                                <h4 class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-snug">{{ $related->title }}</h4>
                                <p class="text-[11px] sm:text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">{{ $related->user->name }}</p>
                                <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-500">{{ number_format($related->views_count ?? 0) }} views • {{ $related->created_at->diffForHumans(null, true) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <script>
        // Define videoPage before Alpine.js starts
        window.videoPage = function() {
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
                
                // Video loading state
                videoReady: false,
                isBuffering: false,
                loadingText: 'Loading...',
                hasPlayed: false,
                
                // Quality selector state
                showQualityMenu: false,
                selectedQuality: 'auto',
                currentAutoQuality: null,
                currentVideoSrc: '',
                availableQualities: @json($video->available_qualities ?? []),
                initialLoad: true,
                
                init() {
                    // Simple initialization - video source is already in HTML
                    const video = document.getElementById('video-player');
                    if (video) {
                        this.currentVideoSrc = video.dataset.streamUrl;
                        this.initQualitySelector();
                        this.initViewTracking();
                        this.initNavigationCleanup();
                    }
                    
                    this.$nextTick(() => {
                        this.initialLoad = false;
                    });
                },
                
                // Cleanup video when navigating away - CRITICAL for fast navigation
                initNavigationCleanup() {
                    const video = document.getElementById('video-player');
                    if (!video) return;
                    
                    // Aggressive cleanup to stop all streaming immediately
                    const cleanup = () => {
                        try {
                            // 1. Pause video immediately
                            if (!video.paused) {
                                video.pause();
                            }
                            
                            // 2. Clear video source to abort any pending requests
                            video.removeAttribute('src');
                            video.load(); // This aborts pending network requests
                            
                            // 3. Remove source elements
                            const sources = video.querySelectorAll('source');
                            sources.forEach(s => s.remove());
                        } catch (e) {
                            // Ignore errors during cleanup
                        }
                    };
                    
                    // Handle all navigation events
                    window.addEventListener('beforeunload', cleanup);
                    window.addEventListener('pagehide', cleanup);
                    
                    // For SPA-like navigation (clicking links)
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('a');
                        if (link && link.href && !link.href.includes('#') && !link.target) {
                            cleanup();
                        }
                    }, true);
                    
                    // Also cleanup when Alpine component is destroyed
                    this.$watch('videoReady', () => {
                        // If component is being destroyed, cleanup
                        if (!document.getElementById('video-player')) {
                            cleanup();
                        }
                    });
                },
                
                // Method to manually stop video (can be called from UI)
                stopVideo() {
                    const video = document.getElementById('video-player');
                    if (video) {
                        video.pause();
                        video.removeAttribute('src');
                        video.load();
                    }
                },
                
                initQualitySelector() {
                    // Load saved quality preference
                    const saved = localStorage.getItem('playtube_quality');
                    
                    // Determine default quality based on device
                    const defaultQuality = this.getDefaultQuality();
                    
                    this.selectedQuality = saved || 'auto';
                    this.currentAutoQuality = defaultQuality;
                    
                    // Check if selected quality exists, fallback to default
                    let initialQuality = this.selectedQuality === 'auto' ? defaultQuality : this.selectedQuality;
                    if (this.selectedQuality !== 'auto' && !this.availableQualities[this.selectedQuality]) {
                        initialQuality = defaultQuality;
                        this.selectedQuality = 'auto';
                    }
                    
                    // Don't change video source on init - let HTML source element handle it
                    // Quality selector only works when user manually changes quality
                },
                
                getDefaultQuality() {
                    const width = window.innerWidth;
                    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                    
                    // Get available qualities sorted from highest to lowest
                    const qualityLadder = ['1080', '720', '480', '360'];
                    const availableList = qualityLadder.filter(q => this.availableQualities[q]);
                    
                    // If no renditions available, return 'original' to use fallback stream
                    if (availableList.length === 0) {
                        return 'original';
                    }
                    
                    // Get highest available quality
                    const highestQuality = availableList[0];
                    
                    // Mobile: prefer lower quality for data saving (480p or lower if available)
                    if (isMobile || width < 480) {
                        return this.availableQualities['480'] ? '480' : 
                               (this.availableQualities['360'] ? '360' : highestQuality);
                    }
                    
                    // Tablet: prefer 720p if available, otherwise highest
                    if (width < 1024) {
                        return this.availableQualities['720'] ? '720' : highestQuality;
                    }
                    
                    // Desktop: prefer highest available quality
                    return highestQuality;
                },
                
                getQualityLabel() {
                    // Return current quality label for button display
                    if (this.selectedQuality === 'auto') {
                        if (this.currentAutoQuality && this.currentAutoQuality !== 'original') {
                            return this.currentAutoQuality + 'p';
                        }
                        return 'Auto';
                    }
                    return this.selectedQuality + 'p';
                },
                
                getQualityUrl(quality) {
                    // Always fallback to base stream URL for 'auto', 'original', or missing quality
                    if (quality === 'auto' || quality === 'original' || !this.availableQualities[quality]) {
                        return '{{ $video->stream_url }}';
                    }
                    
                    return this.availableQualities[quality].url;
                },
                
                setQuality(quality) {
                    if (quality === this.selectedQuality) {
                        this.showQualityMenu = false;
                        return;
                    }
                    
                    const video = document.getElementById('video-player');
                    if (!video) return;
                    
                    // Save state
                    const currentTime = video.currentTime || 0;
                    const wasPaused = video.paused;
                    
                    // Update quality
                    this.selectedQuality = quality;
                    localStorage.setItem('playtube_quality', quality);
                    
                    let newSrc;
                    if (quality === 'auto') {
                        const autoQuality = this.getDefaultQuality();
                        this.currentAutoQuality = autoQuality;
                        newSrc = this.getQualityUrl(autoQuality);
                    } else {
                        newSrc = this.getQualityUrl(quality);
                    }
                    
                    this.currentVideoSrc = newSrc;
                    
                    // Set video src directly (not via source tag)
                    video.src = newSrc;
                    video.load();
                    
                    video.addEventListener('loadedmetadata', function onMeta() {
                        video.removeEventListener('loadedmetadata', onMeta);
                        try {
                            video.currentTime = Math.min(currentTime, video.duration - 0.5);
                        } catch(e) {}
                        
                        if (!wasPaused) {
                            video.play().catch(() => {});
                        }
                    });
                    
                    this.showQualityMenu = false;
                },
                
                setAutoQuality(quality, skipReload = false) {
                    this.currentAutoQuality = quality;
                    this.currentVideoSrc = this.getQualityUrl(quality);
                    
                    // Skip reload during initialization
                    if (skipReload || this.initialLoad) {
                        return;
                    }
                    
                    // Update video src directly
                    const video = document.getElementById('video-player');
                    if (video) {
                        const currentTime = video.currentTime || 0;
                        const wasPaused = video.paused;
                        
                        video.src = this.currentVideoSrc;
                        video.load();
                        
                        video.addEventListener('loadedmetadata', function onMeta() {
                            video.removeEventListener('loadedmetadata', onMeta);
                            try {
                                video.currentTime = Math.min(currentTime, video.duration - 0.5);
                            } catch(e) {}
                            if (!wasPaused) {
                                video.play().catch(() => {});
                            }
                        });
                    }
                },
                
                onBuffering() {
                    // Only show buffering indicator after video has started playing
                    if (this.hasPlayed) {
                        this.isBuffering = true;
                    }
                },
                
                onPlaying() {
                    this.hasPlayed = true;
                    this.isBuffering = false;
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
                },

                openShareModal() {
                    const video = document.getElementById('video-player');
                    const currentTime = video ? video.currentTime : 0;
                    
                    // Try native Web Share API first on mobile
                    if (navigator.share && /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                        navigator.share({
                            title: '{{ $video->title }}',
                            text: '{{ Str::limit(strip_tags($video->description), 100) }}',
                            url: window.location.href
                        }).catch(() => {
                            // Fall back to modal if user cancels or share fails
                            this.dispatchShareModal(currentTime);
                        });
                    } else {
                        this.dispatchShareModal(currentTime);
                    }
                },

                dispatchShareModal(currentTime) {
                    window.dispatchEvent(new CustomEvent('open-share-modal', {
                        detail: {
                            title: '{{ addslashes($video->title) }}',
                            url: '{{ route('video.watch', $video) }}',
                            currentTime: currentTime,
                            showTimestamp: true
                        }
                    }));
                }
            };
        }
    </script>
    @endpush
</x-main-layout>
