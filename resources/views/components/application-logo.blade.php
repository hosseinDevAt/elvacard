@props([
    'variant' => 'gold', // 'gold' | 'dark'
])

@php
$isDark = $variant === 'dark';
$webp = $isDark ? asset('images/logo/logo-dark-128.webp') : asset('images/logo/logo-gold-128.webp');
$png = $isDark ? asset('images/logo/logo-dark-128.png') : asset('images/logo/logo-gold-128.png');
$siteName = site_setting('site_name', config('app.name', 'الواکارت'));
@endphp

<picture class="inline-flex shrink-0">
    <source srcset="{{ $webp }}" type="image/webp">
    <img src="{{ $png }}"
         alt="{{ $siteName }}"
         loading="eager"
         decoding="async"
         {{ $attributes->merge(['class' => 'object-contain select-none pointer-events-none rounded-full']) }}>
</picture>
