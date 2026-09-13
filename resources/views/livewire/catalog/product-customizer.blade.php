<div class="min-h-screen bg-gray-950 text-gray-100 rounded-3xl overflow-hidden shadow-2xl border border-gray-800 flex flex-col font-sans">
    {{-- Header Bar --}}
    <header class="flex items-center justify-between border-b border-gray-800 bg-gray-900/80 px-6 py-4 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-gray-950 font-black shadow-lg shadow-amber-500/20">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </span>
            <span class="text-xl font-extrabold tracking-wider text-white">ElvaCard</span>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-gray-400 hidden sm:inline">
                @if ($step === 1)
                    اطلاعات و انتخاب طرح روی کارت
                @else
                    اطلاعات و مشخصات پشت کارت
                @endif
            </span>
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-400 border border-amber-500/30">
                مرحله {{ $step }} از ۲
            </span>
        </div>
    </header>

    {{-- Main Workspace Content --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-0">
        {{-- Left Control Panel --}}
        <div class="lg:col-span-5 min-w-0 p-6 sm:p-8 bg-gray-900/50 border-b lg:border-b-0 lg:border-e border-gray-800 flex flex-col justify-between space-y-6 overflow-y-auto">
            @if ($step === 1)
                {{-- STEP 1: Front of Card Customization --}}
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-white mb-1">۱. انتخاب طرح لیزر روی کارت</h2>
                        <p class="text-xs text-gray-400">طرح و دسته مورد نظر برای حکاکی روی کارت را انتخاب کنید.</p>
                    </div>

                    {{-- Category Tabs --}}
                    @if (count($categories) > 0)
                        <div class="flex flex-wrap gap-2 border-b border-gray-800 pb-4">
                            @foreach ($categories as $category)
                                <button
                                    type="button"
                                    wire:click="selectCategory({{ $category['id'] }})"
                                    class="rounded-xl px-4 py-2 text-xs font-bold transition duration-200 {{ (int) $selected_category_id === (int) $category['id'] ? 'bg-amber-500 text-gray-950 shadow-md shadow-amber-500/20' : 'bg-gray-800/80 text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                                >
                                    {{ $category['name'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Designs Grid --}}
                    @php
                        $activeCategoryId = $selected_category_id ?? ($categories[0]['id'] ?? null);
                        $designsToDisplay = collect($designs)->where('category_id', $activeCategoryId)->values();
                    @endphp

                    <div class="grid grid-cols-2 gap-3 max-h-[380px] overflow-y-auto pe-1">
                        @forelse ($designsToDisplay as $design)
                            <button
                                type="button"
                                wire:click="selectDesign({{ $design['id'] }})"
                                class="group relative flex flex-col rounded-2xl border p-3 text-start transition duration-200 bg-gray-900/90 overflow-hidden {{ (int) $design_id === (int) $design['id'] ? 'border-amber-500 ring-2 ring-amber-500/50 shadow-lg shadow-amber-500/10' : 'border-gray-800 hover:border-gray-700' }}"
                            >
                                <div class="relative aspect-[16/10] w-full rounded-xl bg-gray-950 overflow-hidden flex items-center justify-center p-2 border border-gray-800">
                                    @if ($design['preview_image_path'])
                                        <img src="{{ asset('storage/' . $design['preview_image_path']) }}" alt="{{ $design['name'] }}" class="h-full w-full object-contain transition group-hover:scale-105">
                                    @else
                                        <div class="flex flex-col items-center text-gray-600">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <span class="mt-2 text-xs font-bold text-gray-200 line-clamp-1 truncate">{{ $design['name'] }}</span>
                            </button>
                        @empty
                            <div class="col-span-2 py-8 text-center text-xs text-gray-500">
                                طرحی در این دسته‌بندی با رنگ انتخابی موجود نیست.
                            </div>
                        @endforelse
                    </div>

                    {{-- Laser Engraving Color Selector --}}
                    @if ($design_id)
                        @php
                            $availableImages = collect($designImages)->where('design_id', $design_id)->values();
                        @endphp
                        @if ($availableImages->isNotEmpty())
                            <div class="pt-2 border-t border-gray-800">
                                <label class="block text-xs font-bold text-gray-300 mb-2">انتخاب رنگ حکاکی لیزری طرح</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($availableImages as $img)
                                        <button
                                            type="button"
                                            wire:click="selectDesignImage({{ $img['id'] }})"
                                            class="flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-medium transition {{ (int) $design_image_id === (int) $img['id'] ? 'border-amber-500 bg-amber-500/10 text-amber-300' : 'border-gray-800 bg-gray-900 text-gray-400 hover:border-gray-700' }}"
                                        >
                                            <span class="h-3 w-3 rounded-full border border-white/20" style="background-color: {{ $img['color_hex'] ?? '#cccccc' }};"></span>
                                            <span>{{ $img['color_name'] ?? 'رنگ لیزر' }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @else
                {{-- STEP 2: Back of Card Specifications --}}
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
            @endif
        </div>

        {{-- Right Panel — Live 2D Fixed-Layout Preview --}}
        <div class="lg:col-span-7 min-w-0 p-6 sm:p-8 bg-gray-950 flex flex-col items-center justify-between space-y-6">
            <div class="w-full flex flex-col items-center space-y-6">
                {{-- Preview Header & Controls --}}
                <div class="w-full flex flex-wrap items-center justify-between gap-4">
                    <h3 class="text-sm font-bold text-gray-300">
                        @if ($step === 1)
                            انتخاب رنگ ورقه فلزی کارت
                        @else
                            نمای پشت کارت فلزی سفارش
                        @endif
                    </h3>

                    {{-- Front / Back View Switcher Tabs --}}
                    <div class="flex items-center rounded-xl bg-gray-900 p-1 border border-gray-800">
                        <button
                            type="button"
                            wire:click="setActiveView('front')"
                            class="rounded-lg px-4 py-1.5 text-xs font-bold transition {{ $activeView === 'front' ? 'bg-amber-500 text-gray-950 shadow' : 'text-gray-400 hover:text-white' }}"
                        >
                            جلو
                        </button>
                        <button
                            type="button"
                            wire:click="setActiveView('back')"
                            class="rounded-lg px-4 py-1.5 text-xs font-bold transition {{ $activeView === 'back' ? 'bg-amber-500 text-gray-950 shadow' : 'text-gray-400 hover:text-white' }}"
                        >
                            پشت
                        </button>
                    </div>
                </div>

                {{-- Color Swatches (Step 1) --}}
                @if ($colorPrices !== [])
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        @foreach ($colorPrices as $cp)
                            @php
                                $isSelected = (int) $color_id === (int) $cp['color_id'];
                            @endphp
                            <button
                                type="button"
                                wire:click="selectColor({{ $cp['color_id'] }})"
                                class="group flex flex-col items-center gap-1 focus:outline-none"
                            >
                                <span class="h-8 w-8 rounded-full border border-white/20 transition-all duration-200 flex items-center justify-center {{ $isSelected ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-gray-950 scale-110' : 'hover:scale-105' }}"
                                      style="background-color: {{ $cp['color_hex'] ?? '#111' }};"
                                ></span>
                                <span class="text-[10px] font-medium {{ $isSelected ? 'text-amber-400 font-bold' : 'text-gray-400' }}">{{ $cp['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- 2D Metallic Card Visualizer Box --}}
                @php
                    $colorName = mb_strtolower($selectedColor['name'] ?? '');
                    $bgGradient = match (true) {
                        str_contains($colorName, 'طلایی') => 'from-amber-400 via-yellow-500 to-amber-600 text-gray-950',
                        str_contains($colorName, 'نقره') => 'from-slate-200 via-gray-300 to-slate-400 text-gray-900',
                        str_contains($colorName, 'مسی') => 'from-amber-700 via-orange-800 to-amber-900 text-amber-100',
                        str_contains($colorName, 'رزگلد') || str_contains($colorName, 'رز') => 'from-rose-300 via-pink-400 to-rose-500 text-gray-900',
                        default => 'from-gray-800 via-gray-900 to-black text-amber-200/90',
                    };
                    $selectedDesignImage = collect($designImages)->firstWhere('id', $design_image_id);
                @endphp

                <div class="w-full max-w-md aspect-[1.586/1] rounded-2xl p-6 shadow-2xl relative overflow-hidden transition-all duration-300 border border-white/10 bg-gradient-to-br {{ $bgGradient }}">
                    {{-- Subtle Card Metallic Shine --}}
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-white/0 via-white/10 to-white/0 opacity-60"></div>

                    @if ($activeView === 'front')
                        {{-- FRONT CARD PREVIEW (Clean layout per Figma M.1: No Chip, No Card Number, No Holder Name, No Royal Bank) --}}
                        <div class="relative h-full flex flex-col justify-between items-center">
                            {{-- Design Overlay Image (if selected) --}}
                            @if ($selectedDesignImage && $selectedDesignImage['image_path'])
                                <div class="absolute inset-0 flex items-center justify-center p-4 opacity-40 pointer-events-none">
                                    <img src="{{ asset('storage/' . $selectedDesignImage['image_path']) }}" alt="Laser Overlay" class="max-h-full max-w-full object-contain">
                                </div>
                            @endif
                        </div>
                    @else
                        @php $slots = \App\Services\Customization\CardPresenter::fixedSlots(); @endphp
                        {{-- BACK CARD PREVIEW WITH FIXED LAYOUT SLOTS --}}
                        <div class="relative h-full w-full select-none">
                            {{-- Top Magnetic Stripe --}}
                            <div class="absolute top-2 inset-x-0 h-10 bg-gray-950 shadow-inner pointer-events-none"></div>

                            {{-- Fixed Slot 1: Card Number --}}
                            @if ($bankCard->card_number !== '')
                                <div class="absolute rounded px-1" style="left: {{ $slots['card_number']['x'] * 100 }}%; top: {{ $slots['card_number']['y'] * 100 }}%;">
                                    <div class="font-mono text-sm font-bold tracking-widest opacity-95" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">
                                        {{ $this->displayCardNumber }}
                                    </div>
                                </div>
                            @endif

                            {{-- Fixed Slot 2: Card Holder Name --}}
                            @if ($bankCard->card_holder_name !== '')
                                <div class="absolute rounded px-1" style="left: {{ $slots['card_holder_name']['x'] * 100 }}%; top: {{ $slots['card_holder_name']['y'] * 100 }}%;">
                                    <div class="h-7 bg-white/90 rounded px-2.5 flex items-center text-gray-900 font-serif italic text-xs font-bold tracking-wider shadow-inner">
                                        {{ $bankCard->card_holder_name }}
                                    </div>
                                </div>
                            @endif

                            {{-- Fixed Slot 3: Back Text --}}
                            @if ($bankCard->back_text !== '')
                                <div class="absolute rounded px-1 max-w-[200px]" style="left: {{ $slots['back_text']['x'] * 100 }}%; top: {{ $slots['back_text']['y'] * 100 }}%;">
                                    <div class="text-xs font-medium italic opacity-90 truncate">
                                        {{ $bankCard->back_text }}
                                    </div>
                                </div>
                            @endif

                            {{-- Fixed Slot 4: CVV2 --}}
                            @if ($bankCard->security_cvv_enabled && $bankCard->cvv2 !== '')
                                <div class="absolute rounded px-1" style="left: {{ $slots['cvv2']['x'] * 100 }}%; top: {{ $slots['cvv2']['y'] * 100 }}%;">
                                    <div class="text-[9px] font-bold tracking-widest opacity-75">CVV2</div>
                                    <div class="font-mono font-bold text-xs tracking-widest" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">
                                        {{ $bankCard->cvv2 }}
                                    </div>
                                </div>
                            @endif

                            {{-- Fixed Slot 5: Expiry Date --}}
                            @if ($bankCard->security_expiry_enabled && ($bankCard->expiry_month !== '' || $bankCard->expiry_year !== ''))
                                <div class="absolute rounded px-1" style="left: {{ $slots['expiry']['x'] * 100 }}%; top: {{ $slots['expiry']['y'] * 100 }}%;">
                                    <div class="text-[9px] font-bold tracking-widest opacity-75">EXPIRES</div>
                                    <div class="font-mono font-bold text-xs tracking-wider" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">
                                        {{ $bankCard->expiry_month ?: '--' }}/{{ $bankCard->expiry_year ?: '--' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Workspace Footer Bar --}}
    <footer class="border-t border-gray-800 bg-gray-900/90 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 backdrop-blur-md">
        {{-- Navigation Actions --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
            @if ($step === 1)
                <button
                    type="button"
                    wire:click="setStep(2)"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-3 w-full sm:w-auto text-xs font-extrabold text-gray-950 transition duration-200 hover:bg-amber-400 shadow-lg shadow-amber-500/20"
                >
                    <span>مرحله بعد: اطلاعات پشت کارت</span>
                    <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @else
                <button
                    type="button"
                    wire:click="setStep(1)"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gray-800 px-4 py-3 w-full sm:w-auto text-xs font-bold text-gray-300 transition hover:bg-gray-700 hover:text-white border border-gray-700"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>بازگشت به مرحله قبل</span>
                </button>

                <button
                    type="button"
                    wire:click="addToCart"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-3 w-full sm:w-auto text-xs font-extrabold text-gray-950 transition duration-200 hover:bg-amber-400 shadow-lg shadow-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove>ثبت نهایی و افزودن به سبد خرید</span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-gray-950" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>در حال ثبت...</span>
                    </span>
                </button>
            @endif
        </div>

        {{-- Step Counter Indicator --}}
        <div class="text-xs font-mono text-gray-400 bg-gray-950/80 px-4 py-1.5 rounded-full border border-gray-800">
            {{ $step }} / ۲
        </div>

        {{-- Dynamic Server-side Total Price Display --}}
        <div class="text-end">
            <span class="text-xs text-gray-400 block">مبلغ قابل پرداخت:</span>
            <span class="text-lg font-black text-amber-400 tracking-tight">
                {{ number_format($totalPrice) }} <span class="text-xs font-normal text-gray-400">تومان</span>
            </span>
        </div>
    </footer>
</div>