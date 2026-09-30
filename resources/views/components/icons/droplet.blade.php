@props(['size' => null])

<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes->except('class')->merge(['class' => icon_class($attributes->get('class'), 'h-5 w-5', $size)]) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a7.5 7.5 0 007.5-7.5c0-4.136-5.25-10.125-6.952-11.744a.75.75 0 00-1.096 0C9.75 3.375 4.5 9.364 4.5 13.5A7.5 7.5 0 0012 21z" />
</svg>