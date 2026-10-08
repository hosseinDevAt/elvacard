@props([
    'scratched' => false,
    'class' => '',
])

@if ($scratched)
    <div {{ $attributes->merge(['class' => 'card-surface-scratched-overlay pointer-events-none absolute inset-0 z-0 overflow-hidden select-none ' . $class]) }} data-scratched-surface="true" aria-hidden="true">
        {{-- High-definition Brushed/Scratched Metallic Texture --}}
        <svg class="h-full w-full object-cover opacity-35 mix-blend-overlay" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
            <defs>
                <pattern id="scratched-metal-pattern" width="120" height="40" patternUnits="userSpaceOnUse">
                    {{-- Micro-hairline horizontal scratches --}}
                    <line x1="0" y1="2" x2="120" y2="2" stroke="#ffffff" stroke-width="0.5" stroke-opacity="0.35" stroke-dasharray="18,6,35,4,22,8" />
                    <line x1="0" y1="5" x2="120" y2="5" stroke="#000000" stroke-width="0.4" stroke-opacity="0.25" stroke-dasharray="10,12,40,6,15,5" />
                    <line x1="0" y1="9" x2="120" y2="9.5" stroke="#ffffff" stroke-width="0.6" stroke-opacity="0.4" stroke-dasharray="25,5,15,8,30,4" />
                    <line x1="0" y1="13" x2="120" y2="13" stroke="#000000" stroke-width="0.3" stroke-opacity="0.2" stroke-dasharray="8,15,22,10,35,7" />
                    <line x1="0" y1="17" x2="120" y2="16.5" stroke="#ffffff" stroke-width="0.5" stroke-opacity="0.3" stroke-dasharray="30,8,20,5,12,6" />
                    <line x1="0" y1="21" x2="120" y2="21" stroke="#000000" stroke-width="0.5" stroke-opacity="0.3" stroke-dasharray="15,4,28,9,18,5" />
                    <line x1="0" y1="25" x2="120" y2="25.5" stroke="#ffffff" stroke-width="0.7" stroke-opacity="0.45" stroke-dasharray="40,6,12,4,25,8" />
                    <line x1="0" y1="29" x2="120" y2="29" stroke="#000000" stroke-width="0.4" stroke-opacity="0.25" stroke-dasharray="12,8,30,6,20,10" />
                    <line x1="0" y1="33" x2="120" y2="32.5" stroke="#ffffff" stroke-width="0.5" stroke-opacity="0.35" stroke-dasharray="20,10,35,5,15,6" />
                    <line x1="0" y1="37" x2="120" y2="37" stroke="#000000" stroke-width="0.3" stroke-opacity="0.2" stroke-dasharray="5,18,25,7,20,8" />
                    {{-- Random fine diagonal scratch accents --}}
                    <line x1="10" y1="3" x2="45" y2="7" stroke="#ffffff" stroke-width="0.4" stroke-opacity="0.3" />
                    <line x1="70" y1="22" x2="105" y2="25" stroke="#ffffff" stroke-width="0.4" stroke-opacity="0.25" />
                    <line x1="25" y1="35" x2="60" y2="38" stroke="#000000" stroke-width="0.3" stroke-opacity="0.2" />
                </pattern>
                <linearGradient id="scratched-shimmer" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.08" />
                    <stop offset="35%" stop-color="#ffffff" stop-opacity="0.18" />
                    <stop offset="50%" stop-color="#000000" stop-opacity="0.12" />
                    <stop offset="65%" stop-color="#ffffff" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0.05" />
                </linearGradient>
            </defs>
            {{-- Base patterned scratch grooves --}}
            <rect width="100%" height="100%" fill="url(#scratched-metal-pattern)" />
            {{-- Linear specular shimmer to give metallic directional reflection --}}
            <rect width="100%" height="100%" fill="url(#scratched-shimmer)" />
        </svg>
    </div>
@endif
