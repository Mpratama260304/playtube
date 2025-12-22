<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'PlayTube') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Alpine.js cloak style -->
    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-900 text-white">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: true, mobileSidebarOpen: false, mobileSearchOpen: false, mobileSearchQuery: '' }">
        <!-- Mobile Search Modal -->
        <div x-show="mobileSearchOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="mobileSearchOpen = false"
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
             style="display: none;">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="mobileSearchOpen = false"></div>
            
            <!-- Modal Content -->
            <div x-show="mobileSearchOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop
                 class="relative w-4/5 max-w-md mx-auto bg-gray-800 rounded-2xl shadow-2xl z-[10000] p-6">
                
                <!-- Search Form -->
                <form action="{{ route('search') }}" method="GET">
                    <!-- Mobile Search Input -->
                    <input type="text" 
                           name="q"
                           id="mobile-search-input"
                           x-model="mobileSearchQuery"
                           x-ref="mobileSearchInput"
                           x-effect="if(mobileSearchOpen) setTimeout(() => $refs.mobileSearchInput.focus(), 150)"
                           placeholder="Search videos..."
                           class="w-full px-4 py-3 bg-gray-700 text-white text-lg placeholder-gray-400 rounded-full focus:outline-none focus:ring-2 focus:ring-red-500 border-0"
                           style="font-size: 18px;"
                           autocomplete="off"
                           inputmode="search"
                           enterkeyhint="search">
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between mt-4">
                        <button type="button" 
                                @click="mobileSearchOpen = false; mobileSearchQuery = ''" 
                                class="px-4 py-2 text-gray-400 hover:text-white text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="mobileSearchQuery.trim().length === 0">
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div x-show="mobileSidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileSidebarOpen = false"
             class="fixed inset-0 bg-black/60 z-40 lg:hidden"
             style="display: none;"></div>

        <!-- Mobile Sidebar Panel -->
        <aside x-show="mobileSidebarOpen"
               x-transition:enter="transition ease-in-out duration-200 transform"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in-out duration-200 transform"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               @click.stop
               class="fixed left-0 top-0 h-full w-72 bg-gray-900 border-r border-gray-800 z-50 lg:hidden overflow-y-auto"
               style="display: none;">
            <!-- Close button -->
            <div class="flex items-center justify-between p-4 border-b border-gray-800">
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                    </svg>
                    <span class="text-xl font-bold text-white">PlayTube</span>
                </a>
                <button @click="mobileSidebarOpen = false" class="p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <!-- Mobile Navigation -->
            <nav class="p-4 space-y-1">
                <a href="{{ route('home') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('home') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Home
                </a>

                <a href="{{ route('shorts.index') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('shorts.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Shorts
                </a>

                <a href="{{ route('trending') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('trending') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Trending
                </a>

                <a href="{{ route('categories.index') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('categories.*') || request()->routeIs('category.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Categories
                </a>

                @auth
                <div class="pt-4 mt-4 border-t border-gray-800">
                    <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">You</h3>
                </div>

                <a href="{{ route('library.history') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('library.history') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    History
                </a>

                <a href="{{ route('playlists.index') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('playlists.*') || request()->routeIs('playlist.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    Playlists
                </a>

                <a href="{{ route('library.watch-later') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('library.watch-later') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Watch Later
                </a>

                <a href="{{ route('library.liked') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('library.liked') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                    Liked Videos
                </a>

                <a href="{{ route('library.subscriptions') }}" @click="mobileSidebarOpen = false" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('library.subscriptions') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Subscriptions
                </a>

                <div class="pt-4 mt-4 border-t border-gray-800">
                    <a href="{{ route('studio.dashboard') }}" @click="mobileSidebarOpen = false" class="flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Creator Studio
                    </a>
                </div>
                @else
                <div class="pt-4 mt-4 border-t border-gray-800">
                    <a href="{{ route('login') }}" @click="mobileSidebarOpen = false" class="flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium transition-colors">
                        Sign In
                    </a>
                </div>
                @endauth
            </nav>
        </aside>

        <!-- Desktop Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            @include('layouts.partials.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
