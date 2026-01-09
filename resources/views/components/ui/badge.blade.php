@props([
    'variant' => 'secondary',
    'size' => 'md',
])

@php
    $baseClasses = 'inline-flex items-center font-medium rounded-md';
    
    $sizes = [
        'sm' => 'px-1.5 py-0.5 text-xs',
        'md' => 'px-2 py-0.5 text-xs',
        'lg' => 'px-2.5 py-1 text-sm',
    ];
    
    $variants = [
        'primary' => 'bg-brand-500/10 text-brand-500 dark:bg-brand-500/20 dark:text-brand-400',
        'secondary' => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
        'success' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        'warning' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        'danger' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        'info' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    ];
    
    $classes = $baseClasses . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['secondary']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
