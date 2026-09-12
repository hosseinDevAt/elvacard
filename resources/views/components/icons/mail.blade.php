@props(['variant' => null])

@php
    $solid = (is_string($variant) && $variant !== '' ? $variant : site_icon_variant('mail')) === 'solid';
@endphp

<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}
     @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" @endif>
    @if ($solid)
        <path fill-rule="evenodd" clip-rule="evenodd" d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 4.4L12 13l8-4.6V6.5l-8 4.6-8-4.6V8.4z" />
    @else
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
    @endif
</svg>