@props(['variant' => null])

@php
    $solid = (is_string($variant) && $variant !== '' ? $variant : site_icon_variant('phone')) === 'solid';
@endphp

<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}
     @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" @endif>
    @if ($solid)
        <path d="M2 6c0-1.51 1.12-2.83 2.57-3.08a.99.99 0 01.63.1l1.73 1.02a1 1 0 01.5.87v2.12a1 1 0 01-.66.94l-1.2.44a9.55 9.55 0 006.06 6.06l.44-1.2a1 1 0 01.94-.66h2.11a1 1 0 01.87.5l1.02 1.73a.99.99 0 01.1.63C20.17 16.89 18.85 18 17.34 18H16.5C6.39 18 2 13.61 2 6.5V6z" />
    @else
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
    @endif
</svg>