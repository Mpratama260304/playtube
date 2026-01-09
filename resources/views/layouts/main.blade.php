<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="$store.theme.mode">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'PlayTube') }}</title>

    {{-- Open Graph / Social Meta Tags --}}
    @hasSection('og-tags')
        @yield('og-tags')
    @else
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $title ?? config('app.name', 'PlayTube') }}">
        <meta property="og:description" content="{{ $metaDescription ?? 'Watch and share videos on PlayTube' }}">
        <meta property="og:site_name" content="{{ config('app.name', 'PlayTube') }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js cloak style -->
    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-white dark:bg-[#0f0f0f] text-gray-900 dark:text-gray-100">
    <div 
        class="min-h-screen" 
        x-data="{
            sidebarOpen: window.innerWidth >= 1024,
            mobileSidebarOpen: false,
            mobileSearchOpen: false,
            mobileSearchQuery: '',
            
            init() {
                window.addEventListener('resize', () => {
                    if (window.innerWidth >= 1024) {
                        this.mobileSidebarOpen = false;
                    }
                });
            },
            
            closeMobileSidebar() {
                this.mobileSidebarOpen = false;
            }
        }"
        @keydown.escape.window="mobileSearchOpen = false; mobileSidebarOpen = false"
    >
        <!-- Mobile Search Modal -->
        <div 
            x-show="mobileSearchOpen" 
            x-cloak
            class="fixed inset-0 z-[60]"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="mobileSearchOpen = false"></div>
            
            <!-- Modal Content -->
            <div 
                class="relative flex items-start justify-center pt-20 px-4"
                x-transition:enter="transition ease-out duration-200 delay-75"
                x-transition:enter-start="opacity-0 -translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                <div 
                    @click.stop
                    class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden"
                >
                    <form action="{{ route('search') }}" method="GET" class="p-4">
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input 
                                type="text" 
                                name="q"
                                x-model="mobileSearchQuery"
                                x-ref="mobileSearchInput"
                                x-effect="if(mobileSearchOpen) $nextTick(() => $refs.mobileSearchInput?.focus())"
                                placeholder="Search videos..."
                                class="w-full pl-12 pr-4 py-3 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white text-lg placeholder-gray-500 rounded-xl border-0 focus:outline-none focus:ring-2 focus:ring-brand-500"
                                autocomplete="off"
                                inputmode="search"
                                enterkeyhint="search"
                            >
                        </div>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button 
                                type="button" 
                                @click="mobileSearchOpen = false; mobileSearchQuery = ''" 
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white text-sm font-medium transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-full transition-colors disabled:opacity-50"
                                :disabled="mobileSearchQuery.trim().length === 0"
                            >
                                Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div 
            x-show="mobileSidebarOpen" 
            x-cloak
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileSidebarOpen = false"
            class="fixed inset-0 bg-black/60 z-40 lg:hidden"
        ></div>

        <!-- Mobile Sidebar Panel -->
        <aside 
            x-show="mobileSidebarOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            @click.stop
            class="fixed left-0 top-0 h-full w-72 bg-white dark:bg-[#0f0f0f] z-50 lg:hidden overflow-y-auto scrollbar-thin"
        >
            <!-- Mobile Sidebar Header -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-800">
                <a href="{{ route('home') }}" class="flex items-center gap-2" @click="closeMobileSidebar()">
                    <svg class="w-8 h-8 text-brand-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                    </svg>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">PlayTube</span>
                </a>
                <button 
                    @click="mobileSidebarOpen = false" 
                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    aria-label="Close sidebar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Navigation -->
            @include('layouts.partials.sidebar-content', ['isMobile' => true])
        </aside>

        <!-- Main Layout Container -->
        <div class="flex">
            <!-- Desktop Sidebar -->
            <aside 
                class="fixed left-0 top-16 bottom-0 z-30 hidden lg:block overflow-y-auto scrollbar-thin bg-white dark:bg-[#0f0f0f] transition-all duration-200"
                :class="sidebarOpen ? 'w-60' : 'w-[72px]'"
            >
                @include('layouts.partials.sidebar-content', ['isMobile' => false])
            </aside>

            <!-- Main Content Area -->
            <div 
                class="flex-1 flex flex-col min-h-screen transition-all duration-200"
                :class="sidebarOpen ? 'lg:ml-60' : 'lg:ml-[72px]'"
            >
                <!-- Header -->
                @include('layouts.partials.header')

                <!-- Page Content -->
                <main class="flex-1 pt-16">
                    <div class="px-4 py-6 lg:px-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <x-share-modal />

    <!-- Toast Notifications -->
    <div 
        x-data="toastNotifications()" 
        @show-toast.window="addToast($event.detail)"
        class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div 
                x-show="toast.visible"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="pointer-events-auto px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[280px] max-w-sm"
                :class="{
                    'bg-green-600 text-white': toast.type === 'success',
                    'bg-red-600 text-white': toast.type === 'error',
                    'bg-blue-600 text-white': toast.type === 'info',
                    'bg-yellow-500 text-gray-900': toast.type === 'warning',
                    'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-900': !toast.type || toast.type === 'default'
                }"
            >
                <!-- Icon -->
                <template x-if="toast.type === 'success'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
                <template x-if="toast.type === 'warning'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </template>
                
                <span class="text-sm font-medium" x-text="toast.message"></span>
                
                <button 
                    @click="removeToast(toast.id)" 
                    class="ml-auto p-1 rounded-full hover:bg-white/20 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <script>
    function toastNotifications() {
        return {
            toasts: [],
            nextId: 0,

            addToast(detail) {
                const id = this.nextId++;
                const toast = {
                    id,
                    message: detail.message || 'Notification',
                    type: detail.type || 'default',
                    visible: true,
                    duration: detail.duration || 3000
                };
                this.toasts.push(toast);

                // Auto-dismiss after duration
                setTimeout(() => {
                    this.removeToast(id);
                }, toast.duration);
            },

            removeToast(id) {
                const toast = this.toasts.find(t => t.id === id);
                if (toast) {
                    toast.visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 200);
                }
            }
        };
    }
    </script>

    @stack('scripts')
</body>
</html>
