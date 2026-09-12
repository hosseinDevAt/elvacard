@props(['variant' => null])

@php
    $solid = (is_string($variant) && $variant !== '' ? $variant : site_icon_variant('map_pin')) === 'solid';
@endphp

<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}
     @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" @endif>
    @if ($solid)
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2a7 7 0 00-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 00-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z" />
    @else
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
    @endif
</svg>