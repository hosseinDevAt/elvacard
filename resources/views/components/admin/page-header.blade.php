@props([
    'title' => null,
    'subtitle' => null,
    'description' => null,
])

@php
    $desc = $subtitle ?? $description;
@endphp

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4']) }}>
    <div>
        @if ($title)
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $title }}</h2>
        @else
            {{ $slot }}
        @endif

        @if ($desc)
            <p class="text-xs text-slate-500 mt-1">{{ $desc }}</p>
        @endif
    </div>

    @if (isset($actions) && $actions->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
