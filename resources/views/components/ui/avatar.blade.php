@props([
    'src' => null,
    'alt' => '',
    'name' => '',
    'size' => 'md',
])

@php
    $sizes = [
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-sm',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-12 h-12 text-base',
        'xl' => 'w-16 h-16 text-lg',
        '2xl' => 'w-20 h-20 text-xl',
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $initial = strtoupper(substr($name ?: $alt, 0, 1));
@endphp

@if($src)
    <img 
        src="{{ $src }}" 
        alt="{{ $alt }}" 
        {{ $attributes->merge(['class' => "$sizeClass rounded-full object-cover"]) }}
    >
@else
    <div {{ $attributes->merge(['class' => "$sizeClass rounded-full bg-brand-500 flex items-center justify-center text-white font-medium"]) }}>
        {{ $initial }}
    </div>
@endif
