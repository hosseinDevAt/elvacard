@props(['variant' => null])

@php
    $solid = (is_string($variant) && $variant !== '' ? $variant : site_icon_variant('search')) === 'solid';
@endphp

<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}
     @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" @endif>
    @if ($solid)
        <path fill-rule="evenodd" clip-rule="evenodd" d="M10 4a6 6 0 104.12 10.31l5.28 5.29a1 1 0 001.42-1.42l-5.3-5.28A6 6 0 0010 4zm0 2a4 4 0 100 8 4 4 0 000-8z" />
    @else
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    @endif
</svg>