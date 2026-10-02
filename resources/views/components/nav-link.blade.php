@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold text-[#ffde5b] border-b-2 border-[#ffde5b] bg-white/[0.06] focus:outline-none transition-all duration-150'
            : 'inline-flex items-center px-2.5 py-1 rounded-lg border-b-2 border-transparent text-sm font-medium text-slate-300 hover:text-white hover:text-[#ffde5b] hover:bg-white/[0.04] focus:outline-none transition-all duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>