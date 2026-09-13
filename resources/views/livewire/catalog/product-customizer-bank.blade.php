{{-- BANK CARD WORKSPACE: Step 2 back-of-card specifications. --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-bold text-white mb-1">۲. مشخصات اطلاعات پشت کارت</h2>
        <p class="text-xs text-gray-400">اطلاعات واقعی که می‌خواهید پشت کارت حک شود را وارد کنید.</p>
    </div>

    {{-- Card Number Input --}}
    <div class="space-y-1.5">
        <label for="card_number" class="block text-xs font-bold text-gray-300">
            شماره کارت (۱۶ رقمی)
        </label>
        <input
            id="card_number"
            type="text"
            inputmode="numeric"
            autocomplete="off"
            maxlength="16"
            wire:model.live.debounce.150ms="bankCard.card_number"
            placeholder="6274 0512 3456 7890"
            oninput="this.value = this.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^0-9]/g, '').slice(0, 16)"
            class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white font-mono dir-ltr text-start placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
        >
        @error('bankCard.card_number')
            <p class="text-xs text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Cardholder Name Input --}}
    <div class="space-y-1.5">
        <label for="card_holder_name" class="block text-xs font-bold text-gray-300">
            نام دارنده کارت (لاتین)
        </label>
        <input
            id="card_holder_name"
            type="text"
            autocomplete="off"
            maxlength="100"
            wire:model.live.debounce.150ms="bankCard.card_holder_name"
            placeholder="AMIR HOSSEIN REZAIE"
            class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
        >
        @error('bankCard.card_holder_name')
            <p class="text-xs text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Back Custom Text Input --}}
    <div class="space-y-1.5">
        <label for="back_text" class="block text-xs font-bold text-gray-300">
            متن دلخواه یا جمله اختصاصی (حکاکی پشت)
        </label>
        <input
            id="back_text"
            type="text"
            autocomplete="off"
            maxlength="255"
            wire:model.live.debounce.150ms="bankCard.back_text"
            placeholder="مثال: Born to Lead"
            class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
        >
        @error('bankCard.back_text')
            <p class="text-xs text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Independent Security Toggles --}}
    <div class="space-y-3 rounded-2xl border border-gray-800 bg-gray-950/60 p-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-gray-200">حکاکی اطلاعات امنیتی کارت</span>
            <span class="rounded bg-gray-800 px-2 py-0.5 text-[10px] font-semibold text-gray-400">اختیاری</span>
        </div>

        {{-- Toggle 1: CVV2 --}}
        <div class="space-y-2 pt-2 border-t border-gray-800/80">
            <div class="flex items-center justify-between">
                <label for="toggle-cvv" class="text-xs font-medium text-gray-300 cursor-pointer select-none">
                    حکاکی CVV2
                </label>
                <button
                    id="toggle-cvv"
                    type="button"
                    role="switch"
                    aria-checked="{{ $bankCard->security_cvv_enabled ? 'true' : 'false' }}"
                    wire:click="toggleCvv"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $bankCard->security_cvv_enabled ? 'bg-amber-500' : 'bg-gray-800' }}"
                >
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $bankCard->security_cvv_enabled ? 'translate-x-0' : '-translate-x-5' }}"></span>
                </button>
            </div>

            @if ($bankCard->security_cvv_enabled)
                <div class="pt-2">
                    <label for="cvv2" class="block text-[10px] text-gray-400 mb-1">مقدار CVV2 واقعی</label>
                    <input
                        id="cvv2"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        maxlength="4"
                        wire:model.live.debounce.150ms="bankCard.cvv2"
                        placeholder="مثال: 314"
                        oninput="this.value = this.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^0-9]/g, '').slice(0, 4)"
                        class="w-full rounded-lg border border-gray-800 bg-gray-900 px-3 py-1.5 text-xs text-white font-mono dir-ltr focus:border-amber-500 focus:outline-none"
                    >
                    @error('bankCard.cvv2')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </div>

        {{-- Toggle 2: Expiry Date --}}
        <div class="space-y-2 pt-2 border-t border-gray-800/80">
            <div class="flex items-center justify-between">
                <label for="toggle-expiry" class="text-xs font-medium text-gray-300 cursor-pointer select-none">
                    حکاکی تاریخ انقضا
                </label>
                <button
                    id="toggle-expiry"
                    type="button"
                    role="switch"
                    aria-checked="{{ $bankCard->security_expiry_enabled ? 'true' : 'false' }}"
                    wire:click="toggleExpiry"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $bankCard->security_expiry_enabled ? 'bg-amber-500' : 'bg-gray-800' }}"
                >
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $bankCard->security_expiry_enabled ? 'translate-x-0' : '-translate-x-5' }}"></span>
                </button>
            </div>

            @if ($bankCard->security_expiry_enabled)
                <div class="grid grid-cols-2 gap-2 pt-2">
                    <div>
                        <label class="block text-[10px] text-gray-400 mb-1">ماه انقضا</label>
                        <select wire:model.live="bankCard.expiry_month" class="w-full rounded-lg border border-gray-800 bg-gray-900 px-2 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
                            <option value="">انتخاب ماه...</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ sprintf('%02d', $m) }}">{{ sprintf('%02d', $m) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-400 mb-1">سال انقضا (دو رقم)</label>
                        <select wire:model.live="bankCard.expiry_year" class="w-full rounded-lg border border-gray-800 bg-gray-900 px-2 py-1.5 text-xs text-white focus:border-amber-500 focus:outline-none">
                            <option value="">انتخاب سال...</option>
                            @for ($y = (int) now()->format('y'); $y <= (int) now()->format('y') + 10; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Notice Box --}}
    <div class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-4 text-xs text-amber-300/90 leading-relaxed flex items-start gap-2.5">
        <svg class="h-5 w-5 shrink-0 text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>توجه: ممکن است موقعیت عناصر روی کارت چاپی نهایی با توجه به ابعاد دقیق لیزر کمی با پیش‌نمایش تفاوت داشته باشد.</span>
    </div>
</div>