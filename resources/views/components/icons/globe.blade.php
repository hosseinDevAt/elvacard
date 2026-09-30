@props(['size' => null])

<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes->except('class')->merge(['class' => icon_class($attributes->get('class'), 'h-5 w-5', $size)]) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 100-18 9 9 0 000 18zM2.25 12h19.5M12 3a15.15 15.15 0 014.5 9 15.15 15.15 0 01-4.5 9 15.15 15.15 0 01-4.5-9A15.15 15.15 0 0112 3z" />
</svg>