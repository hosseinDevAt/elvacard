@props(['size' => null])

<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes->except('class')->merge(['class' => icon_class($attributes->get('class'), 'h-5 w-5', $size)]) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a9 9 0 100 18 1 1 0 001-1v-1.5a1.5 1.5 0 011.5-1.5H16a4 4 0 004-4 7.5 7.5 0 00-8-8z" />
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 10.5a.75.75 0 100-1.5.75.75 0 000 1.5zM10.75 6.75a.75.75 0 100-1.5.75.75 0 000 1.5zM15.75 6.75a.75.75 0 100-1.5.75.75 0 000 1.5z" />
</svg>