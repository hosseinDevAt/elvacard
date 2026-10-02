@php
    $initialToasts = [];
    if (session()->has('success')) {
        $initialToasts[] = ['type' => 'success', 'message' => session('success'), 'title' => 'موفقیت‌آمیز'];
    }
    if (session()->has('error')) {
        $initialToasts[] = ['type' => 'error', 'message' => session('error'), 'title' => 'خطا'];
    }
    if (session()->has('status')) {
        $initialToasts[] = ['type' => 'info', 'message' => session('status'), 'title' => 'اطلاعیه'];
    }
    if (session()->has('info')) {
        $initialToasts[] = ['type' => 'info', 'message' => session('info'), 'title' => 'اطلاعیه'];
    }
    if (session()->has('warning')) {
        $initialToasts[] = ['type' => 'warning', 'message' => session('warning'), 'title' => 'هشدار'];
    }
@endphp

<div
    x-data="toastContainer(@js($initialToasts))"
    class="fixed bottom-5 left-5 z-[110] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none select-none"
    dir="rtl"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            @mouseenter="pause(toast)"
            @mouseleave="resume(toast)"
            class="pointer-events-auto flex items-start gap-3 rounded-2xl bg-white p-4 shadow-lg border text-right transition-all transform"
            :class="{
                'border-emerald-200 text-slate-800 shadow-emerald-500/10': toast.type === 'success',
                'border-rose-200 text-slate-800 shadow-rose-500/10': toast.type === 'error',
                'border-amber-200 text-slate-800 shadow-amber-500/10': toast.type === 'warning',
                'border-indigo-200 text-slate-800 shadow-indigo-500/10': toast.type === 'info' || toast.type === 'primary'
            }"
            :role="toast.type === 'error' || toast.type === 'warning' ? 'alert' : 'status'"
        >
            {{-- Icon Badge --}}
            <div class="shrink-0 mt-0.5">
                <template x-if="toast.type === 'success'">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200/60">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </template>
                <template x-if="toast.type === 'error'">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-200/60">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                </template>
                <template x-if="toast.type === 'warning'">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-200/60">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </template>
                <template x-if="toast.type === 'info' || toast.type === 'primary'">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-[#010619]/10 text-[#010619] ring-1 ring-[#010619]/20">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </template>
            </div>

            {{-- Text Message --}}
            <div class="flex-1 min-w-0">
                <template x-if="toast.title">
                    <h4 class="text-xs font-bold text-slate-900 mb-0.5" x-text="toast.title"></h4>
                </template>
                <p class="text-xs text-slate-700 leading-relaxed break-words" x-text="toast.message"></p>
            </div>

            {{-- Close Button --}}
            <button
                type="button"
                @click="remove(toast.id)"
                class="shrink-0 text-slate-400 hover:text-slate-600 rounded-lg p-1 transition"
                aria-label="بستن"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
