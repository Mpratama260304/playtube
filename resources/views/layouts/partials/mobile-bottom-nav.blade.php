{{-- Mobile Bottom Navigation - Only visible on mobile --}}
<nav class="pt-bottom-nav md:hidden" aria-label="Mobile navigation">
    <div class="pt-bottom-nav-inner">
        {{-- Home --}}
        <a href="{{ route('home') }}" 
           class="pt-bottom-nav-item {{ request()->routeIs('home') ? 'active' : '' }}"
           aria-label="Home"
        >
            <svg fill="{{ request()->routeIs('home') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                @if(request()->routeIs('home'))
                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                @endif
            </svg>
            <span>Home</span>
        </a>

        {{-- Shorts/Explore --}}
        <a href="{{ route('shorts.index') }}" 
           class="pt-bottom-nav-item {{ request()->routeIs('shorts.*') ? 'active' : '' }}"
           aria-label="Shorts"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Shorts</span>
        </a>

        {{-- Upload (for authenticated users) --}}
        @auth
        <a href="{{ route('studio.upload') }}" 
           class="pt-bottom-nav-item"
           aria-label="Upload"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Upload</span>
        </a>
        @else
        <a href="{{ route('login') }}" 
           class="pt-bottom-nav-item"
           aria-label="Sign in"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Upload</span>
        </a>
        @endauth

        {{-- Subscriptions --}}
        @auth
        <a href="{{ route('library.subscriptions') }}" 
           class="pt-bottom-nav-item {{ request()->routeIs('library.subscriptions') ? 'active' : '' }}"
           aria-label="Subscriptions"
        >
            <svg fill="{{ request()->routeIs('library.subscriptions') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span>Subs</span>
        </a>
        @else
        <a href="{{ route('login') }}" 
           class="pt-bottom-nav-item"
           aria-label="Subscriptions"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span>Subs</span>
        </a>
        @endauth

        {{-- Library/Profile --}}
        @auth
        <a href="{{ route('library.index') }}" 
           class="pt-bottom-nav-item {{ request()->routeIs('library.*') ? 'active' : '' }}"
           aria-label="Library"
        >
            <svg fill="{{ request()->routeIs('library.*') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <span>Library</span>
        </a>
        @else
        <a href="{{ route('login') }}" 
           class="pt-bottom-nav-item"
           aria-label="Sign in"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Sign in</span>
        </a>
        @endauth
    </div>
</nav>
