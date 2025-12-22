<!-- Header -->
<header class="sticky top-0 z-30 bg-gray-900 border-b border-gray-800">
    <div class="flex items-center justify-between h-16 px-4">
        <!-- Left: Menu & Logo -->
        <div class="flex items-center space-x-4">
            <!-- Desktop sidebar toggle -->
            <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:block p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <!-- Mobile hamburger - MUST work -->
            <button @click="mobileSidebarOpen = true" class="lg:hidden p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800 relative z-50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <a href="{{ route('home') }}" class="flex items-center space-x-2 lg:hidden">
                <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                </svg>
                <span class="text-xl font-bold text-white">PlayTube</span>
            </a>
        </div>

        <!-- Center: Search (Desktop) -->
        <div class="flex-1 max-w-2xl mx-4 hidden sm:block">
            <form action="{{ route('search') }}" method="GET" class="relative flex items-center">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" 
                       name="q" 
                       value="{{ request('q') }}"
                       placeholder="Search videos..." 
                       class="w-full h-10 px-4 py-2 pl-10 pr-12 bg-gray-800 border border-gray-700 rounded-full text-white placeholder-gray-400 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-500"
                       autocomplete="off">
                <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center z-10 hover:text-white">
                    <svg class="w-5 h-5 text-gray-400 hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center space-x-2">
            <!-- Mobile Search Button (opens modal) -->
            <button @click="mobileSearchOpen = true" class="sm:hidden p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            @auth
                <!-- Upload -->
                <a href="{{ route('studio.upload') }}" class="p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800" title="Upload">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </a>

                <!-- Notifications -->
                <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if(auth()->user()->unreadUserNotifications()->count() > 0)
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-600 rounded-full"></span>
                    @endif
                </a>

                <!-- User Menu with Teleport for proper z-index -->
                <div class="relative" x-data="{
                    open: false,
                    dropdownStyle: '',
                    toggle() {
                        this.open = !this.open;
                        if (this.open) {
                            this.$nextTick(() => this.position());
                        }
                    },
                    position() {
                        const btn = this.$refs.userBtn;
                        if (!btn) return;
                        
                        const rect = btn.getBoundingClientRect();
                        const width = 256; // w-64 = 16rem = 256px
                        const margin = 8;
                        
                        // Align right edge of dropdown to right edge of button
                        let left = rect.right - width;
                        let top = rect.bottom + margin;
                        
                        // Clamp to viewport
                        left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));
                        
                        // If dropdown would overflow bottom, show above button
                        const estHeight = 400;
                        if (top + estHeight > window.innerHeight - margin) {
                            top = Math.max(margin, rect.top - margin - estHeight);
                        }
                        
                        this.dropdownStyle = `left: ${left}px; top: ${top}px;`;
                    },
                    init() {
                        window.addEventListener('resize', () => { if (this.open) this.position(); });
                        window.addEventListener('scroll', () => { if (this.open) this.position(); }, true);
                    }
                }">
                    <button x-ref="userBtn" @click="toggle()" class="flex items-center space-x-2 p-1 rounded-full hover:bg-gray-800">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover">
                        @else
                            <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white font-medium">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                    </button>

                    <!-- Teleported Dropdown - renders at body level to avoid z-index/overflow issues -->
                    <template x-teleport="body">
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.away="open = false"
                             @keydown.escape.window="open = false"
                             class="fixed w-64 max-h-[70vh] overflow-y-auto bg-gray-800 rounded-lg shadow-xl border border-gray-700 py-2 z-[9999]"
                             :style="dropdownStyle"
                             style="display: none;">
                            <!-- User Info -->
                            <div class="px-4 py-3 border-b border-gray-700">
                                <div class="flex items-center space-x-3">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center text-white font-medium">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-white">{{ auth()->user()->name }}</p>
                                        <p class="text-sm text-gray-400">@{{ auth()->user()->username }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2">
                                <a href="{{ route('channel.show', auth()->user()->username) }}" @click="open = false" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Your Channel
                                </a>
                                <a href="{{ route('studio.dashboard') }}" @click="open = false" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Creator Studio
                                </a>
                                <a href="{{ route('messages.index') }}" @click="open = false" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    Messages
                                </a>
                                <a href="{{ route('settings.index') }}" @click="open = false" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Settings
                                </a>
                            </div>

                            @if(auth()->user()->isAdmin())
                            <div class="border-t border-gray-700 py-2">
                                <a href="/admin" @click="open = false" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    Admin Panel
                                </a>
                            </div>
                            @endif

                            <div class="border-t border-gray-700 py-2">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-gray-300 hover:bg-gray-700">
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>
            @else
                <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    Sign In
                </a>
            @endauth
        </div>
    </div>
</header>
