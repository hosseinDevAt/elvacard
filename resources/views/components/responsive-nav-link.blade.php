@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2.5 border-s-4 border-[#ffde5b] text-start text-base font-bold text-[#ffde5b] bg-[#ffde5b]/10 rounded-e-lg focus:outline-none transition-all duration-150'
            : 'block w-full ps-3 pe-4 py-2.5 border-s-4 border-transparent text-start text-base font-medium text-slate-300 hover:text-white hover:text-[#ffde5b] hover:bg-white/5 rounded-e-lg focus:outline-none transition-all duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>