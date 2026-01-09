@props(['isMobile' => false])

@php
    $closeSidebar = $isMobile ? '@click="closeMobileSidebar()"' : '';
@endphp

<div class="flex flex-col h-full py-3">
    <!-- Navigation -->
    <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
        <!-- Main Navigation -->
        <div class="pb-3 mb-3 border-b border-gray-200 dark:border-gray-800">
            <a href="{{ route('home') }}" {!! $closeSidebar !!} 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('home') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mx-auto': !sidebarOpen && !{{ $isMobile ? 'true' : 'false' }} }" fill="{{ request()->routeIs('home') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span x-show="sidebarOpen || {{ $isMobile ? 'true' : 'false' }}" class="text-sm">Home</span>
            </a>

            <a href="{{ route('shorts.index') }}" {!! $closeSidebar !!}
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('shorts.*') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <span x-show="sidebarOpen || {{ $isMobile ? 'true' : 'false' }}" class="text-sm">Shorts</span>
            </a>

            <a href="{{ route('trending') }}" {!! $closeSidebar !!}
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('trending') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>
                </svg>
                <span x-show="sidebarOpen || {{ $isMobile ? 'true' : 'false' }}" class="text-sm">Trending</span>
            </a>

            <a href="{{ route('categories.index') }}" {!! $closeSidebar !!}
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('categories.*') || request()->routeIs('category.*') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span x-show="sidebarOpen || {{ $isMobile ? 'true' : 'false' }}" class="text-sm">Categories</span>
            </a>
        </div>

        @auth
        <!-- You Section -->
        <div x-show="sidebarOpen || {{ $isMobile ? 'true' : 'false' }}" class="pb-3 mb-3 border-b border-gray-200 dark:border-gray-800">
            <h3 class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">You</h3>
            
            <a href="{{ route('library.history') }}" {!! $closeSidebar !!}
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('library.history') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm">History</span>
            </a>

            <a href="{{ route('playlists.index') }}" {!! $closeSidebar !!}
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('playlists.*') || request()->routeIs('playlist.*') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                <span class="text-sm">Playlists</span>
            </a>

            <a href="{{ route('library.watch-later') }}" {!! $closeSidebar !!}
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('library.watch-later') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm">Watch Later</span>
            </a>

            <a href="{{ route('library.liked') }}" {!! $closeSidebar !!}
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('library.liked') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                </svg>
                <span class="text-sm">Liked Videos</span>
            </a>

            <a href="{{ route('library.subscriptions') }}" {!! $closeSidebar !!}
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                      {{ request()->routeIs('library.subscriptions') 
                         ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-medium' 
                         : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span class="text-sm">Subscriptions</span>
            </a>
        </div>
        @endauth
    </nav>

    <!-- Bottom Section -->
    <div class="px-3 mt-auto pt-3 border-t border-gray-200 dark:border-gray-800">
        @auth
            <a href="{{ route('studio.dashboard') }}" {!! $closeSidebar !!}
               class="flex items-center justify-center gap-2 px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-lg text-sm font-medium transition-colors"
               x-show="sidebarOpen || {{ $isMobile ? 'true' : 'false' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <span>Creator Studio</span>
            </a>
            
            <!-- Collapsed state icon -->
            <a href="{{ route('studio.dashboard') }}" {!! $closeSidebar !!}
               class="flex items-center justify-center p-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-lg transition-colors"
               x-show="!sidebarOpen && !{{ $isMobile ? 'true' : 'false' }}"
               title="Creator Studio"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </a>
        @else
            <a href="{{ route('login') }}" {!! $closeSidebar !!}
               class="flex items-center justify-center gap-2 px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-lg text-sm font-medium transition-colors"
               x-show="sidebarOpen || {{ $isMobile ? 'true' : 'false' }}"
            >
                Sign In
            </a>
            
            <a href="{{ route('login') }}" {!! $closeSidebar !!}
               class="flex items-center justify-center p-2.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
               x-show="!sidebarOpen && !{{ $isMobile ? 'true' : 'false' }}"
               title="Sign In"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </a>
        @endauth
    </div>
</div>
