<header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/95 backdrop-blur px-4 py-3.5 sm:px-8">
    <div class="flex items-center justify-between gap-4">
        <!-- Title & Breadcrumb -->
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                @click="sidebarOpen = !sidebarOpen"
                :aria-expanded="sidebarOpen"
                aria-label="باز کردن منو"
                class="lg:hidden inline-flex items-center p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
            >
                <x-icons.menu-toggle x-var="sidebarOpen" />
            </button>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-xs font-medium text-slate-400 mb-0.5">
                    <span>ElvaCard</span>
                    <svg class="h-3 w-3 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    @if($sectionLabel)
                        <span class="text-slate-300">/</span>
                        <span class="text-slate-600 font-semibold">{{ $sectionLabel }}</span>
                    @endif
                </div>
                <h1 class="truncate text-xl font-extrabold tracking-tight text-slate-900">{{ $title ?? 'پنل ادمین' }}</h1>
            </div>
        </div>

        <!-- Right Actions (Date) -->
        <div class="flex shrink-0 items-center gap-3">
            <!-- Date Badge -->
            <span class="hidden items-center gap-1.5 rounded-2xl border border-slate-200/80 bg-slate-50/80 px-3.5 py-2 text-xs font-semibold text-slate-600 md:inline-flex">
                <x-icons.sparkles class="h-3.5 w-3.5 text-[#e0b719]" />
                {{ jalali_now('date') }}
            </span>
        </div>
    </div>
</header>