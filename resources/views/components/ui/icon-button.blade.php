@props([
    'variant' => 'ghost',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'label' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center rounded-full transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-500 dark:focus-visible:ring-offset-gray-900';
    
    $sizes = [
        'sm' => 'p-1.5',
        'md' => 'p-2',
        'lg' => 'p-3',
    ];
    
    $variants = [
        'ghost' => 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white',
        'subtle' => 'text-gray-500 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
        'primary' => 'text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-900/20',
    ];
    
    $classes = $baseClasses . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['ghost']);
@endphp

@if($href)
    <a 
        href="{{ $href }}" 
        {{ $attributes->merge(['class' => $classes]) }}
        @if($label) aria-label="{{ $label }}" title="{{ $label }}" @endif
    >
        {{ $slot }}
    </a>
@else
    <button 
        type="{{ $type }}" 
        {{ $attributes->merge(['class' => $classes]) }}
        @if($label) aria-label="{{ $label }}" title="{{ $label }}" @endif
    >
        {{ $slot }}
    </button>
@endif
