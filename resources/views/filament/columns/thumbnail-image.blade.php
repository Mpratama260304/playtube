@php
    $url = $getState();
    $width = $getExtraAttributes()['width'] ?? '80px';
    $height = $getExtraAttributes()['height'] ?? '45px';
@endphp

<div class="flex items-center justify-center">
    <img 
        src="{{ $url }}" 
        alt="Thumbnail" 
        class="rounded object-cover"
        style="width: {{ $width }}; height: {{ $height }}; min-width: {{ $width }};"
        loading="lazy"
        onerror="this.onerror=null; this.src='/images/placeholder-thumb.svg';"
    >
</div>
