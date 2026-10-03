@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center justify-between w-full px-4 py-3 rounded-xl text-sm font-extrabold text-[#ffde5b] bg-[#ffde5b]/10 border border-[#ffde5b]/30 shadow-sm shadow-[#ffde5b]/10 focus:outline-none transition-all duration-150'
            : 'flex items-center justify-between w-full px-4 py-3 rounded-xl text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/5 hover:text-[#ffde5b] focus:outline-none transition-all duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>