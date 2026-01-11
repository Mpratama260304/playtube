<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="$store.theme.mode">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Sign In' }} - {{ config('app.name', 'PlayTube') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
            
            /* Subtle background pattern */
            .auth-bg-pattern {
                background-image: 
                    radial-gradient(circle at 25% 25%, rgba(255, 0, 51, 0.03) 0%, transparent 50%),
                    radial-gradient(circle at 75% 75%, rgba(62, 166, 255, 0.03) 0%, transparent 50%);
            }
            .dark .auth-bg-pattern {
                background-image: 
                    radial-gradient(circle at 25% 25%, rgba(255, 0, 51, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 75% 75%, rgba(62, 166, 255, 0.05) 0%, transparent 50%);
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-surface-dark auth-bg-pattern">
            <!-- Minimal Header Bar -->
            <header class="h-14 sm:h-16 flex items-center justify-between px-4 sm:px-6 bg-white/80 dark:bg-surface-dark/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-800">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <svg class="w-8 h-8 text-brand-500 transition-transform group-hover:scale-105" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                    </svg>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">PlayTube</span>
                </a>

                <!-- Theme Toggle -->
                <button 
                    @click="$store.theme.toggle()" 
                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    aria-label="Toggle theme"
                >
                    <!-- Sun icon (shown in dark mode) -->
                    <svg x-show="$store.theme.mode === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <!-- Moon icon (shown in light mode) -->
                    <svg x-show="$store.theme.mode === 'light'" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
            </header>

            <!-- Main Content -->
            <main class="flex-1 flex flex-col items-center justify-center px-4 py-8 sm:py-12">
                <!-- Auth Card -->
                <div class="w-full sm:max-w-md">
                    <div class="bg-white dark:bg-surface-dark-elevated rounded-2xl shadow-xl dark:shadow-2xl border border-gray-200 dark:border-gray-800 px-6 py-8 sm:px-8 sm:py-10">
                        {{ $slot }}
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>&copy; {{ date('Y') }} {{ config('app.name', 'PlayTube') }}. All rights reserved.</p>
                </div>
            </main>
        </div>
    </body>
</html>
