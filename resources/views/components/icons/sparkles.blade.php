@props(['size' => null])

<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes->except('class')->merge(['class' => icon_class($attributes->get('class'), 'h-5 w-5', $size)]) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9.55 16.77a.75.75 0 01-1.44.1l-.298-1.028a5.192 5.192 0 00-3.654-3.654l-1.028-.298a.75.75 0 010-1.44l1.028-.298a5.192 5.192 0 003.654-3.654l.298-1.028a.75.75 0 011.44.1l.264.867 6.119 6.387-3.571 7.595zM19.5 5.25l-.206.937a1.5 1.5 0 01-1.107 1.107l-.937.206.937.206a1.5 1.5 0 011.107 1.107l.206.937.206-.937a1.5 1.5 0 011.107-1.107l.937-.206-.937-.206A1.5 1.5 0 0119.707 4.34L19.5 5.25z" />
</svg>