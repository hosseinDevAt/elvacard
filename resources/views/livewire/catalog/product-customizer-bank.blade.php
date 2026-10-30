{{-- BANK CARD WORKSPACE: Step 2 back-of-card specifications. --}}
<div class="space-y-6">
    <div>
        <h2 class="text-base font-bold text-[#010619] mb-1">۲. مشخصات اطلاعات پشت و روی کارت</h2>
        <p class="text-xs text-slate-500">اطلاعات واقعی که می‌خواهید روی کارت فلزی حک شود را وارد کنید.</p>
    </div>

    {{-- IBAN Input (Optional) --}}
    <div class="space-y-1.5">
        <div class="flex items-center justify-between">
            <label for="iban" class="block text-xs font-bold text-slate-700">
                شماره شبا
            </label>
            <span class="rounded bg-[#F3F1EB] px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-[#DDD9CF]">اختیاری</span>
        </div>
        <div class="relative flex items-center rounded-xl border border-[#DDD9CF] bg-white focus-within:border-[#ffde5b] focus-within:ring-1 focus-within:ring-[#ffde5b] overflow-hidden" dir="ltr" style="direction: ltr !important;">
            <span class="inline-flex items-center justify-center px-3.5 py-2.5 bg-[#F8F7F3] border-r border-[#DDD9CF] text-xs font-mono font-bold text-[#8A6A00] select-none shrink-0" aria-hidden="true" style="direction: ltr !important;">
                IR
            </span>
            <input
                id="iban"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                maxlength="26"
                wire:model.live.debounce.150ms="bankCard.iban"
                placeholder="۲۴ رقم شماره شبا"
                dir="ltr"
                style="direction: ltr !important;"
                class="w-full bg-transparent px-3.5 py-2.5 text-sm text-[#010619] font-mono text-left placeholder-slate-400 focus:outline-none"
            >
        </div>
        @error('bankCard.iban')
            <p class="text-xs text-rose-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Card Number Input --}}
    <div class="space-y-1.5">
        <label for="card_number" class="block text-xs font-bold text-slate-700">
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
            class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] font-mono dir-ltr text-start placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]"
        >
        @error('bankCard.card_number')
            <p class="text-xs text-rose-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Cardholder Name Input --}}
    <div class="space-y-1.5">
        <label for="card_holder_name" class="block text-xs font-bold text-slate-700">
            نام دارنده کارت (لاتین)
        </label>
        <input
            id="card_holder_name"
            type="text"
            autocomplete="off"
            maxlength="100"
            wire:model.live.debounce.150ms="bankCard.card_holder_name"
            placeholder="AMIR HOSSEIN REZAIE"
            class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]"
        >
        @error('bankCard.card_holder_name')
            <p class="text-xs text-rose-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Custom Text Input & Placement Selector --}}
    <div class="space-y-2 rounded-2xl border border-[#DDD9CF] bg-[#F8F7F3] p-4">
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="back_text" class="block text-xs font-bold text-slate-700">
                    متن دلخواه یا جمله اختصاصی (حکاکی پشت)
                </label>
                <span class="rounded bg-[#F3F1EB] px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-[#DDD9CF]">اختیاری</span>
            </div>
            <input
                id="back_text"
                type="text"
                autocomplete="off"
                maxlength="255"
                wire:model.live.debounce.150ms="bankCard.back_text"
                placeholder="مثال: Born to Lead"
                class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]"
            >
            @error('bankCard.back_text')
                <p class="text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Placement Toggle: Back vs Front --}}
        <div class="pt-2 border-t border-[#DDD9CF]">
            <span class="block text-[11px] font-medium text-slate-600 mb-1.5">محل حکاکی متن دلخواه:</span>
            <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-white border border-[#DDD9CF]" role="radiogroup" aria-label="محل حکاکی متن دلخواه">
                <button
                    type="button"
                    wire:click="$set('bankCard.custom_text_placement', 'back')"
                    role="radio"
                    aria-checked="{{ ($bankCard->custom_text_placement ?? 'back') === 'back' ? 'true' : 'false' }}"
                    class="h-9 rounded-lg px-3 text-xs font-bold transition-all duration-150 cursor-pointer flex items-center justify-center gap-1.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ffde5b] {{ ($bankCard->custom_text_placement ?? 'back') === 'back' ? 'bg-[#ffde5b] text-[#010619] shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-[#F3F1EB]' }}"
                >
                    <span>پشت کارت</span>
                    <span class="text-[10px] opacity-75">(پیش‌فرض)</span>
                </button>
                <button
                    type="button"
                    wire:click="$set('bankCard.custom_text_placement', 'front')"
                    role="radio"
                    aria-checked="{{ ($bankCard->custom_text_placement ?? 'back') === 'front' ? 'true' : 'false' }}"
                    class="h-9 rounded-lg px-3 text-xs font-bold transition-all duration-150 cursor-pointer flex items-center justify-center gap-1.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ffde5b] {{ ($bankCard->custom_text_placement ?? 'back') === 'front' ? 'bg-[#ffde5b] text-[#010619] shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-[#F3F1EB]' }}"
                >
                    <span>روی کارت</span>
                    <span class="text-[10px] opacity-75">(جلو)</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Independent Security Toggles --}}
    <div class="space-y-3 rounded-2xl border border-[#DDD9CF] bg-[#F8F7F3] p-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-[#010619]">حکاکی اطلاعات امنیتی کارت</span>
            <span class="rounded bg-[#F3F1EB] px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-[#DDD9CF]">اختیاری</span>
        </div>

        {{-- Toggle 1: CVV2 --}}
        <div class="space-y-2 pt-2 border-t border-[#DDD9CF]">
            <div class="flex items-center justify-between">
                <label for="toggle-cvv" class="text-xs font-medium text-slate-700 cursor-pointer select-none">
                    حکاکی CVV2
                </label>
                <button
                    id="toggle-cvv"
                    type="button"
                    role="switch"
                    aria-checked="{{ $bankCard->security_cvv_enabled ? 'true' : 'false' }}"
                    wire:click="toggleCvv"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $bankCard->security_cvv_enabled ? 'bg-[#ffde5b]' : 'bg-slate-300' }}"
                >
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $bankCard->security_cvv_enabled ? 'translate-x-0' : '-translate-x-5' }}"></span>
                </button>
            </div>

            @if ($bankCard->security_cvv_enabled)
                <div class="pt-2">
                    <label for="cvv2" class="block text-[10px] text-slate-600 mb-1">مقدار CVV2 واقعی</label>
                    <input
                        id="cvv2"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        maxlength="4"
                        wire:model.live.debounce.150ms="bankCard.cvv2"
                        placeholder="مثال: 314"
                        oninput="this.value = this.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^0-9]/g, '').slice(0, 4)"
                        class="w-full rounded-lg border border-[#DDD9CF] bg-white px-3 py-1.5 text-xs text-[#010619] font-mono dir-ltr focus:border-[#ffde5b] focus:outline-none"
                    >
                    @error('bankCard.cvv2')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </div>

        {{-- Toggle 2: Expiry Date --}}
        <div class="space-y-2 pt-2 border-t border-[#DDD9CF]">
            <div class="flex items-center justify-between">
                <label for="toggle-expiry" class="text-xs font-medium text-slate-700 cursor-pointer select-none">
                    حکاکی تاریخ انقضا
                </label>
                <button
                    id="toggle-expiry"
                    type="button"
                    role="switch"
                    aria-checked="{{ $bankCard->security_expiry_enabled ? 'true' : 'false' }}"
                    wire:click="toggleExpiry"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $bankCard->security_expiry_enabled ? 'bg-[#ffde5b]' : 'bg-slate-300' }}"
                >
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $bankCard->security_expiry_enabled ? 'translate-x-0' : '-translate-x-5' }}"></span>
                </button>
            </div>

            @if ($bankCard->security_expiry_enabled)
                @php
                    [$expiryYearFrom, $expiryYearTo] = \App\Services\BankCard\BankCardCustomization::expiryYearRange();
                @endphp
                <div class="grid grid-cols-2 gap-2 pt-2">
                    <div>
                        <label class="block text-[10px] text-slate-600 mb-1">ماه انقضا (شمسی)</label>
                        <select wire:model.live="bankCard.expiry_month"
                                style="background-position: left 0.5rem center; padding-left: 1.75rem; padding-right: 0.625rem;"
                                class="w-full rounded-lg border border-[#DDD9CF] bg-white py-1.5 text-xs text-[#010619] focus:border-[#ffde5b] focus:outline-none">
                            <option value="">انتخاب ماه...</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ sprintf('%02d', $m) }}">{{ fa_digits(sprintf('%02d', $m)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-600 mb-1">سال انقضا (شمسی)</label>
                        <select wire:model.live="bankCard.expiry_year"
                                style="background-position: left 0.5rem center; padding-left: 1.75rem; padding-right: 0.625rem;"
                                class="w-full rounded-lg border border-[#DDD9CF] bg-white py-1.5 text-xs text-[#010619] focus:border-[#ffde5b] focus:outline-none">
                            <option value="">انتخاب سال...</option>
                            @for ($y = $expiryYearFrom; $y <= $expiryYearTo; $y++)
                                <option value="{{ $y }}">{{ fa_digits($y) }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Notice Box --}}
    <div class="rounded-2xl border border-[#ffde5b] bg-[#FFFBEB] p-4 text-xs text-slate-700 leading-relaxed flex items-start gap-2.5">
        <svg class="h-5 w-5 shrink-0 text-[#8A6A00] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>توجه: ممکن است موقعیت عناصر روی کارت چاپی نهایی با توجه به ابعاد دقیق لیزر کمی با پیش‌نمایش تفاوت داشته باشد.</span>
    </div>
</div>