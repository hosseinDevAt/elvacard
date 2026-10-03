@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3.5 py-2 rounded-xl text-sm font-extrabold text-[#ffde5b] bg-[#ffde5b]/10 border border-[#ffde5b]/30 shadow-sm shadow-[#ffde5b]/10 focus:outline-none transition-all duration-200'
            : 'inline-flex items-center px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/[0.08] hover:text-[#ffde5b] focus:outline-none transition-all duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>