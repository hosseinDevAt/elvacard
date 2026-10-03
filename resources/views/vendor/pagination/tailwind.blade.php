@if ($paginator->hasPages())
    <nav role="navigation" aria-label="ناوبری صفحات" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-xs font-bold text-slate-400 bg-white border border-slate-200 cursor-not-allowed rounded-xl">
                    قبلی
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                    قبلی
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                    بعدی
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 text-xs font-bold text-slate-400 bg-white border border-slate-200 cursor-not-allowed rounded-xl">
                    بعدی
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-slate-500">
                    نمایش
                    @if ($paginator->firstItem())
                        <span class="font-bold text-slate-800">{{ fa_number($paginator->firstItem()) }}</span>
                        تا
                        <span class="font-bold text-slate-800">{{ fa_number($paginator->lastItem()) }}</span>
                    @else
                        {{ fa_number($paginator->count()) }}
                    @endif
                    از
                    <span class="font-bold text-slate-800">{{ fa_number($paginator->total()) }}</span>
                    نتیجه
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-xl shadow-xs -space-x-px space-x-reverse">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="صفحه قبلی">
                            <span class="relative inline-flex items-center px-2.5 py-2 rounded-r-xl border border-slate-200 bg-white text-xs font-medium text-slate-300 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-2.5 py-2 rounded-r-xl border border-slate-200 bg-white text-xs font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition" aria-label="صفحه قبلی">
                            <svg class="w-4 h-4 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3.5 py-2 border border-slate-200 bg-white text-xs font-medium text-slate-400 cursor-default">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-3.5 py-2 border border-[#010619] bg-[#010619] text-xs font-bold text-[#ffde5b]">{{ fa_number($page) }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-3.5 py-2 border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 transition" aria-label="صفحه {{ fa_number($page) }}">
                                        {{ fa_number($page) }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-2.5 py-2 rounded-l-xl border border-slate-200 bg-white text-xs font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition" aria-label="صفحه بعدی">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="صفحه بعدی">
                            <span class="relative inline-flex items-center px-2.5 py-2 rounded-l-xl border border-slate-200 bg-white text-xs font-medium text-slate-300 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
