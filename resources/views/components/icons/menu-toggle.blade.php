@props(['xVar' => 'open'])

<svg stroke="currentColor" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['class' => 'h-6 w-6']) }}>
    <path :class="{ 'hidden': {{ $xVar }}, 'inline-flex': !{{ $xVar }} }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    <path :class="{ 'hidden': !{{ $xVar }}, 'inline-flex': {{ $xVar }} }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
</svg>