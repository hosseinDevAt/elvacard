@if ($paginator->hasPages())
    <nav role="navigation" aria-label="ناوبری صفحات طرح‌ها" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-200/80">
        {{-- Mobile view: Compact, accessible bar with arrows and Persian text --}}
        <div class="flex items-center justify-between w-full sm:hidden">
            {{-- Previous Page Button (In RTL, previous is on the right -> Arrow Right) --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-400 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed select-none opacity-60" aria-disabled="true">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>صفحه قبل</span>
                </span>
            @else
                <button
                    type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 hover:text-slate-900 active:bg-slate-100 shadow-sm transition duration-150 cursor-pointer"
                    aria-label="صفحه قبل"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>صفحه قبل</span>
                </button>
            @endif

            {{-- Page indicator on mobile --}}
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            {{-- Next Page Button (In RTL, next is on the left -> Arrow Left) --}}
            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 hover:text-slate-900 active:bg-slate-100 shadow-sm transition duration-150 cursor-pointer"
                    aria-label="صفحه بعد"
                >
                    <span>صفحه بعد</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-400 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed select-none opacity-60" aria-disabled="true">
                    <span>صفحه بعد</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @endif
        </div>

        {{-- Desktop view: Detailed page numbers and counter --}}
        <div class="hidden sm:flex sm:items-center sm:justify-between sm:w-full">
            <div>
                <p class="text-xs text-slate-500">
                    نمایش
                    <span class="font-bold text-slate-800">{{ $paginator->firstItem() }}</span>
                    تا
                    <span class="font-bold text-slate-800">{{ $paginator->lastItem() }}</span>
                    از
                    <span class="font-bold text-slate-800">{{ $paginator->total() }}</span>
                    طرح
                </p>
            </div>

            <div class="flex items-center gap-1">
                {{-- Previous Button --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 border border-slate-200 cursor-not-allowed select-none" aria-disabled="true">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                @else
                    <button
                        type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer"
                        aria-label="صفحه قبل"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @endif

                {{-- Page Elements --}}
                @foreach ($elements as $element)
                    {{-- Separator --}}
                    @if (is_string($element))
                        <span class="inline-flex items-center justify-center w-8 h-8 text-xs font-bold text-slate-400">
                            {{ $element }}
                        </span>
                    @endif

                    {{-- Page Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-extrabold bg-[#ffde5b] text-[#010619] shadow-sm select-none" aria-current="page">
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 transition cursor-pointer"
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Button --}}
                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer"
                        aria-label="صفحه بعد"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                @else
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 border border-slate-200 cursor-not-allowed select-none" aria-disabled="true">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
