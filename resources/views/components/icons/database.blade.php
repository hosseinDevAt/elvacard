@props(['size' => null])

<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes->except('class')->merge(['class' => icon_class($attributes->get('class'), 'h-5 w-5', $size)]) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7.5c0-1.38-3.582-2.5-8-2.5S4 6.12 4 7.5m16 0v5c0 1.38-3.582 2.5-8 2.5s-8-1.12-8-2.5v-5m16 0v5c0 1.38-3.582 2.5-8 2.5s-8-1.12-8-2.5v-5m0 10v5c0 1.38 3.582 2.5 8 2.5s8-1.12 8-2.5v-5" />
</svg>