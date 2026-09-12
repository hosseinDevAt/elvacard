@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-accent-400 text-sm font-medium leading-5 text-accent-300 focus:outline-none focus:border-accent-500 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-white/70 hover:text-white hover:border-accent-400/60 focus:outline-none focus:text-white focus:border-accent-400/60 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>