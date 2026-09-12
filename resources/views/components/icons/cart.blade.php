@props(['variant' => null])

@php
    $solid = (is_string($variant) && $variant !== '' ? $variant : site_icon_variant('cart')) === 'solid';
@endphp

<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}
     @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" @endif>
    @if ($solid)
        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.4 4.5l-.42-1.26a1 1 0 00-1.9.63l.42 1.26.98 2.94 1.77 5.63a2 2 0 001.87 1.35h8.26a2 2 0 001.9-1.42l1.76-5.95a1 1 0 00-.97-1.32H6.75l-.63-1.86zM9 17.75a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm6.25 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" />
    @else
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 006 19h11a1 1 0 00.9-.6L19 13M9 21a1 1 0 100-2 1 1 0 000 2zm1-8a1 1 0 100-2 1 1 0 000 2z" />
    @endif
</svg>