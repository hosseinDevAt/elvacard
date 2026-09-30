@props(['size' => null])

<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes->except('class')->merge(['class' => icon_class($attributes->get('class'), 'h-5 w-5', $size)]) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a2.25 2.25 0 00-1.59-.66H5.25A2.25 2.25 0 003 5.25v14.25a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 17.25V10.5a2.25 2.25 0 00-2.25-2.25h-6.72a2.25 2.25 0 01-1.59-.66z" />
</svg>