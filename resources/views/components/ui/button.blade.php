@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs rounded-lg gap-1.5',
        'md' => 'px-4 py-2 text-sm rounded-lg gap-2',
        'lg' => 'px-6 py-3 text-base rounded-xl gap-2.5',
    ];
    
    $variants = [
        'primary' => 'bg-brand-500 text-white hover:bg-brand-600 focus-visible:ring-brand-500 dark:focus-visible:ring-offset-gray-900',
        'secondary' => 'bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700 focus-visible:ring-gray-500 dark:focus-visible:ring-offset-gray-900',
        'ghost' => 'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 focus-visible:ring-gray-500 dark:focus-visible:ring-offset-gray-900',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500 dark:focus-visible:ring-offset-gray-900',
        'outline' => 'border border-gray-300 dark:border-gray-600 bg-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 focus-visible:ring-gray-500 dark:focus-visible:ring-offset-gray-900',
    ];
    
    $classes = $baseClasses . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes, 'disabled' => $disabled]) }}>
        {{ $slot }}
    </button>
@endif
