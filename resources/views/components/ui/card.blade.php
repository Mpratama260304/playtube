@props([
    'padding' => true,
    'hover' => false,
])

@php
    $classes = 'bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800';
    
    if ($padding) {
        $classes .= ' p-4';
    }
    
    if ($hover) {
        $classes .= ' transition-shadow duration-200 hover:shadow-md';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
