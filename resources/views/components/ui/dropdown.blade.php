@props([
    'align' => 'right',
    'width' => '56',
])

@php
    $alignClasses = [
        'left' => 'left-0 origin-top-left',
        'right' => 'right-0 origin-top-right',
        'center' => 'left-1/2 -translate-x-1/2 origin-top',
    ];
    
    $widthClasses = [
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
    ];
@endphp

<div 
    x-data="{ open: false }" 
    @click.away="open = false"
    @keydown.escape.window="open = false"
    class="relative"
    {{ $attributes }}
>
    {{-- Trigger --}}
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    {{-- Dropdown Content --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute {{ $alignClasses[$align] ?? $alignClasses['right'] }} {{ $widthClasses[$width] ?? $widthClasses['56'] }} mt-2 rounded-xl shadow-lg overflow-hidden z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
        style="display: none;"
        @click="open = false"
    >
        {{ $slot }}
    </div>
</div>
