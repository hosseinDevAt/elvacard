@props(['items' => [], 'dark' => false])

@if(!empty($items))
    <nav aria-label="خرده‌نان" class="mb-6 flex items-center text-xs {{ $dark ? 'text-slate-400' : 'text-slate-500' }} overflow-x-auto whitespace-nowrap py-1">
        <ol class="inline-flex items-center gap-1.5 sm:gap-2">
            <li class="inline-flex items-center">
                <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-1.5 {{ $dark ? 'text-slate-400 hover:text-[#ffde5b]' : 'text-slate-500 hover:text-[#010619]' }} transition">
                    <x-icons.home class="w-3.5 h-3.5 shrink-0 {{ $dark ? 'text-slate-500' : 'text-slate-400' }}" />
                    <span>خانه</span>
                </a>
            </li>
            @foreach($items as $item)
                <li class="inline-flex items-center gap-1.5 sm:gap-2">
                    <span class="{{ $dark ? 'text-slate-600' : 'text-slate-300' }} shrink-0">/</span>
                    @if(!empty($item['url']) && !$loop->last)
                        <a href="{{ $item['url'] }}" wire:navigate class="{{ $dark ? 'text-slate-400 hover:text-[#ffde5b]' : 'text-slate-500 hover:text-[#010619]' }} transition">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="font-bold {{ $dark ? 'text-white' : 'text-slate-800' }} truncate max-w-[200px] sm:max-w-none" aria-current="page">
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
