<div
    x-data="confirmationModal()"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="if (isOpen) cancel()"
    @keydown.tab="trapFocus($event)"
>
    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        @click="cancel()"
        aria-hidden="true"
    ></div>

    {{-- Modal Card --}}
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 transition-all text-right"
        dir="rtl"
        @click.stop
    >
        <div class="flex items-start gap-4">
            {{-- Contextual Icon --}}
            <div class="shrink-0">
                <template x-if="variant === 'danger'">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-1 ring-rose-200/60">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </span>
                </template>

                <template x-if="variant === 'warning'">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-1 ring-amber-200/60">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </template>

                <template x-if="variant === 'success'">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200/60">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </template>

                <template x-if="variant === 'primary' || variant === 'info'">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#010619]/10 text-[#010619] ring-1 ring-[#010619]/20">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </template>
            </div>

            {{-- Text Content --}}
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-bold text-slate-900" x-text="title"></h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 whitespace-pre-line" x-text="message"></p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button
                type="button"
                x-ref="cancelBtn"
                @click="cancel()"
                :disabled="isProcessing"
                class="admin-btn admin-btn-secondary text-xs sm:text-sm py-2 px-4"
            >
                <span x-text="cancelText">انصراف</span>
            </button>

            <button
                type="button"
                x-ref="confirmBtn"
                @click="confirm()"
                :disabled="isProcessing"
                class="admin-btn text-xs sm:text-sm py-2 px-4 shadow-sm"
                :class="{
                    'admin-btn-danger': variant === 'danger',
                    'bg-amber-600 hover:bg-amber-700 text-white focus-visible:ring-amber-400': variant === 'warning',
                    'admin-btn-success': variant === 'success',
                    'admin-btn-primary': variant === 'primary' || variant === 'info'
                }"
            >
                <span x-show="!isProcessing" x-text="confirmText">تایید</span>
                <span x-show="isProcessing" class="inline-flex items-center gap-1.5" x-cloak>
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    در حال پردازش...
                </span>
            </button>
        </div>
    </div>
</div>
