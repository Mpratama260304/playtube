@props([
    'type' => 'text',
    'variant' => 'default',
])

@php
    $baseClasses = 'w-full transition-colors duration-200 focus:outline-none';
    
    $variants = [
        'default' => 'px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-700 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-accent focus:border-transparent',
        'search' => 'pl-10 pr-4 py-2.5 rounded-full border bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-transparent placeholder-gray-500 dark:placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:border-gray-300 dark:focus:border-gray-600 focus:ring-0',
        'ghost' => 'px-4 py-2.5 rounded-lg bg-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700',
    ];
    
    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['default']);
@endphp

<input 
    type="{{ $type }}" 
    {{ $attributes->merge(['class' => $classes]) }}
>
