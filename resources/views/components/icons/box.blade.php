@props(['size' => null])

<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes->except('class')->merge(['class' => icon_class($attributes->get('class'), 'h-5 w-5', $size)]) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25 12 3 3 8.25m18 0L12 13.5M3 8.25 12 13.5m9-5.25v9.75L12 21m0-7.5V21m0-7.5L3 13.5m18 0-9 5.25" />
</svg>