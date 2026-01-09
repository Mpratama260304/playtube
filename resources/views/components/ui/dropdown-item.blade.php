@props([
    'href' => null,
    'icon' => null,
])

@php
    $classes = 'flex items-center w-full px-4 py-2.5 text-sm text-left text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="w-5 h-5 mr-3 flex-shrink-0">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        @if($icon)
            <span class="w-5 h-5 mr-3 flex-shrink-0">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
