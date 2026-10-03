{{-- Workflow constants: branches are literal and compile-time, never dynamic. --}}
@php
    $bankCardWorkflow = \App\Enums\CustomizationWorkflowEnum::BANK_CARD->value;
    $fuelCardWorkflow = \App\Enums\CustomizationWorkflowEnum::FUEL_CARD->value;
@endphp
<div class="min-h-screen bg-[#010619] text-slate-100 rounded-3xl overflow-hidden shadow-2xl border border-[#152244] flex flex-col font-sans">
    {{-- Header Bar --}}
    <header class="flex items-center justify-between border-b border-[#152244] bg-[#070e24]/90 px-6 py-4 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#ffde5b] text-[#010619] font-black shadow-md shadow-[#ffde5b]/20">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </span>
            <span class="text-xl font-extrabold tracking-wider text-white">الواکارت</span>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-slate-300 hidden sm:inline">
                @if ($step === 1)
                    اطلاعات و انتخاب طرح روی کارت
                @else
                    اطلاعات و مشخصات پشت کارت
                @endif
            </span>
            <span class="rounded-full bg-[#ffde5b]/15 px-3 py-1 text-xs font-bold text-[#ffde5b] border border-[#ffde5b]/30">
                مرحله {{ $step }} از ۲
            </span>
        </div>
    </header>

    {{-- Main Workspace Content --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-0">
        {{-- Left Control Panel --}}
        <div class="lg:col-span-5 min-w-0 p-6 sm:p-8 bg-[#070e24]/60 border-b lg:border-b-0 lg:border-e border-[#152244] flex flex-col justify-between space-y-6 overflow-y-auto">
            @if ($step === 1)
                {{-- STEP 1: Front of Card Customization --}}
                <div class="space-y-6">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1">۱. انتخاب طرح لیزر روی کارت</h2>
                        <p class="text-xs text-slate-400">طرح و دسته مورد نظر برای حکاکی روی کارت را انتخاب کنید.</p>
                    </div>

                    {{-- Category Tabs --}}
                    @if (count($categories) > 0)
                        <div class="flex flex-wrap gap-2 border-b border-[#152244] pb-4">
                            @foreach ($categories as $category)
                                <button
                                    type="button"
                                    wire:click="selectCategory({{ $category['id'] }})"
                                    class="rounded-xl px-4 py-2 text-xs font-bold transition duration-200 cursor-pointer {{ (int) $selected_category_id === (int) $category['id'] ? 'bg-[#ffde5b] text-[#010619] shadow-md shadow-[#ffde5b]/20' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-700/60' }}"
                                >
                                    {{ $category['name'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Designs Grid --}}
                    <div class="grid grid-cols-2 gap-3 max-h-[380px] overflow-y-auto pe-1">
                        @forelse ($designs as $design)
                            <button
                                type="button"
                                wire:click="selectDesign({{ $design['id'] }})"
                                class="group relative flex flex-col rounded-2xl border p-3 text-start transition duration-200 bg-[#010619]/90 overflow-hidden cursor-pointer {{ (int) $design_id === (int) $design['id'] ? 'border-[#ffde5b] ring-2 ring-[#ffde5b]/50 shadow-lg shadow-[#ffde5b]/10' : 'border-[#152244] hover:border-slate-700' }}"
                            >
                                <div class="relative aspect-[16/10] w-full rounded-xl bg-black/50 overflow-hidden flex items-center justify-center p-2 border border-[#152244]">
                                    @if ($design['preview_image_path'])
                                        <img src="{{ asset('storage/' . $design['preview_image_path']) }}" alt="{{ $design['name'] }}" class="h-full w-full object-contain transition duration-200 group-hover:scale-105">
                                    @else
                                        <div class="flex flex-col items-center text-slate-600">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <span class="mt-2 text-xs font-bold text-slate-200 line-clamp-1 truncate">{{ $design['name'] }}</span>
                            </button>
                        @empty
                            <div class="col-span-2 py-8 text-center text-xs text-slate-500">
                                طرحی در این دسته‌بندی با رنگ انتخابی موجود نیست.
                            </div>
                        @endforelse
                    </div>

                    @if ($designs->hasPages())
                        <div class="pt-1">
                            {{ $designs->links() }}
                        </div>
                    @endif

                    {{-- Laser Engraving Color Selector --}}
                    @if ($design_id)
                        @php
                            $availableImages = collect($designImages)->where('design_id', $design_id)->values();
                        @endphp
                        @if ($availableImages->isNotEmpty())
                            <div class="pt-2 border-t border-[#152244]">
                                <label class="block text-xs font-bold text-slate-300 mb-2">انتخاب رنگ حکاکی لیزری طرح</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($availableImages as $img)
                                        <button
                                            type="button"
                                            wire:click="selectDesignImage({{ $img['id'] }})"
                                            class="flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-medium transition cursor-pointer {{ (int) $design_image_id === (int) $img['id'] ? 'border-[#ffde5b] bg-[#ffde5b]/10 text-[#ffde5b]' : 'border-[#152244] bg-[#010619] text-slate-400 hover:border-slate-700' }}"
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
                {{-- STEP 2: Workflow-specific back-of-card workspace --}}
                @if ($workflow === $bankCardWorkflow)
                    @include('livewire.catalog.product-customizer-bank')
                @elseif ($workflow === $fuelCardWorkflow)
                    @include('livewire.catalog.product-customizer-fuel')
                @endif
            @endif
        </div>

        {{-- Right Panel — Live 2D Fixed-Layout Preview --}}
        <div class="lg:col-span-7 min-w-0 p-6 sm:p-8 bg-[#010619] flex flex-col items-center justify-between space-y-6">
            <div class="w-full flex flex-col items-center space-y-6">
                {{-- Preview Header & Controls --}}
                <div class="w-full flex flex-wrap items-center justify-between gap-4">
                    <h3 class="text-xs font-bold text-slate-300">
                        @if ($step === 1)
                            انتخاب رنگ ورقه فلزی کارت
                        @else
                            نمای پشت کارت فلزی سفارش
                        @endif
                    </h3>

                    {{-- Front / Back View Switcher Tabs --}}
                    <div class="flex items-center rounded-xl bg-[#070e24] p-1 border border-[#152244]">
                        <button
                            type="button"
                            wire:click="setActiveView('front')"
                            class="rounded-lg px-4 py-1.5 text-xs font-bold transition cursor-pointer {{ $activeView === 'front' ? 'bg-[#ffde5b] text-[#010619] shadow-sm' : 'text-slate-400 hover:text-white' }}"
                        >
                            جلو
                        </button>
                        <button
                            type="button"
                            wire:click="setActiveView('back')"
                            class="rounded-lg px-4 py-1.5 text-xs font-bold transition cursor-pointer {{ $activeView === 'back' ? 'bg-[#ffde5b] text-[#010619] shadow-sm' : 'text-slate-400 hover:text-white' }}"
                        >
                            پشت
                        </button>
                    </div>
                </div>

                {{-- Color Swatches (Step 1) --}}
                @if ($workflow === $fuelCardWorkflow)
                    @if ($selectedColor)
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <div class="flex flex-col items-center gap-1">
                                <span class="h-8 w-8 rounded-full border border-white/20" style="background-color: {{ $selectedColor['color_hex'] ?? '#111' }};"></span>
                                <span class="text-[10px] font-medium text-slate-400">{{ $selectedColor['name'] }}</span>
                            </div>
                        </div>
                    @endif
                @elseif ($colorPrices !== [])
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        @foreach ($colorPrices as $cp)
                            @php
                                $isSelected = (int) $color_id === (int) $cp['color_id'];
                            @endphp
                            <button
                                type="button"
                                wire:click="selectColor({{ $cp['color_id'] }})"
                                class="group flex flex-col items-center gap-1 focus:outline-none cursor-pointer"
                            >
                                <span class="h-8 w-8 rounded-full border border-white/20 transition-all duration-200 flex items-center justify-center {{ $isSelected ? 'ring-2 ring-[#ffde5b] ring-offset-2 ring-offset-[#010619] scale-110' : 'hover:scale-105' }}"
                                      style="background-color: {{ $cp['color_hex'] ?? '#111' }};"
                                ></span>
                                <span class="text-[10px] font-medium {{ $isSelected ? 'text-[#ffde5b] font-bold' : 'text-slate-400' }}">{{ $cp['name'] }}</span>
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
                        {{-- FRONT CARD PREVIEW --}}
                        <div class="relative h-full flex flex-col justify-between items-center">
                            {{-- Design Overlay Image (if selected) --}}
                            @if ($selectedDesignImage && $selectedDesignImage['image_path'])
                                <div class="absolute inset-0 flex items-center justify-center p-4 opacity-40 pointer-events-none">
                                    <img src="{{ asset('storage/' . $selectedDesignImage['image_path']) }}" alt="Laser Overlay" class="max-h-full max-w-full object-contain">
                                </div>
                            @endif

                            {{-- Decorative Sample Chip Element for Fuel Card FRONT --}}
                            @if ($workflow === $fuelCardWorkflow)
                                <div class="absolute top-1/2 -translate-y-1/2 start-6 h-9 w-12 rounded-md bg-gradient-to-br from-amber-300 via-yellow-400 to-amber-600 border border-yellow-100/70 shadow-md flex items-center justify-center pointer-events-none opacity-95">
                                    <div class="w-full h-[1px] bg-amber-800/40"></div>
                                    <div class="absolute inset-y-0 w-[1px] bg-amber-800/40"></div>
                                    <div class="h-4 w-6 rounded-[2px] border border-amber-800/30 bg-amber-400/20"></div>
                                </div>
                            @endif
                        </div>
                    @elseif ($workflow === $bankCardWorkflow)
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
                                        {{ $bankCard->expiry_month ? fa_digits($bankCard->expiry_month) : '--' }}/{{ $bankCard->expiry_year ? fa_digits($bankCard->expiry_year) : '--' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- FUEL BACK CARD PREVIEW WITH STRUCTURED SPECIFICATION TABLE --}}
                        <div class="relative h-full w-full select-none p-4 flex flex-col justify-between text-white">
                            {{-- Structured Specification Table --}}
                            <div class="w-full my-auto overflow-hidden">
                                <table class="w-full text-start border-collapse text-xs text-slate-100 border border-white/20 rounded-lg overflow-hidden">
                                    <tbody>
                                        <tr class="border-b border-white/20">
                                            <th scope="row" class="py-1.5 px-3 text-start font-bold text-slate-300 w-2/5 bg-white/10">نام مالک</th>
                                            <td class="py-1.5 px-3 font-bold text-white">{{ $fuelCard->owner_name ?: '---' }}</td>
                                        </tr>
                                        <tr class="border-b border-white/20">
                                            <th scope="row" class="py-1.5 px-3 text-start font-bold text-slate-300 bg-white/10">اطلاعات خودرو</th>
                                            <td class="py-1.5 px-3 text-white">{{ $fuelCard->car_info ?: '---' }}</td>
                                        </tr>
                                        <tr class="border-b border-white/20">
                                            <th scope="row" class="py-1.5 px-3 text-start font-bold text-slate-300 bg-white/10">شماره شاسی (VIN)</th>
                                            <td class="py-1.5 px-3 font-mono font-bold text-white dir-ltr text-start" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">{{ $fuelCard->vin ?: '---' }}</td>
                                        </tr>
                                        <tr class="border-b border-white/20">
                                            <th scope="row" class="py-1.5 px-3 text-start font-bold text-slate-300 bg-white/10">نام سامانه</th>
                                            <td class="py-1.5 px-3 text-white">{{ $fuelCard->system_name ?: '---' }}</td>
                                        </tr>
                                        <tr class="border-b border-white/20">
                                            <th scope="row" class="py-1.5 px-3 text-start font-bold text-slate-300 bg-white/10">شناسه سامانه</th>
                                            <td class="py-1.5 px-3 font-mono text-slate-200 dir-ltr text-start" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">{{ $fuelCard->system_identifier ?: '---' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="py-1.5 px-3 text-start font-bold text-slate-300 bg-white/10">شماره پلاک</th>
                                            <td class="py-1.5 px-3 font-bold text-white dir-ltr text-start" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">{{ $fuelCard->plate_number ?: '---' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Bottom Footer --}}
                            <div class="flex items-center justify-between pt-1 border-t border-white/20 text-[10px] text-slate-300">
                                <span>مشخصات کارت سوخت اختصاصی</span>
                                <span class="font-mono opacity-60">ELVA-FUEL</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Workspace Footer Bar --}}
    <footer class="border-t border-[#152244] bg-[#070e24]/90 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 backdrop-blur-md">
        {{-- Navigation Actions --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
            @if ($step === 1)
                <button
                    type="button"
                    wire:click="setStep(2)"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#ffde5b] px-6 py-3 w-full sm:w-auto text-xs font-bold text-[#010619] transition duration-200 hover:bg-[#f5d347] active:scale-[0.99] shadow-md shadow-[#ffde5b]/20 cursor-pointer"
                >
                    <span>مرحله بعد: اطلاعات پشت کارت</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @else
                <button
                    type="button"
                    wire:click="setStep(1)"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800 px-5 py-3 w-full sm:w-auto text-xs font-bold text-slate-300 transition hover:bg-slate-700 hover:text-white border border-slate-700 cursor-pointer"
                >
                    <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>بازگشت به مرحله قبل</span>
                </button>

                <button
                    type="button"
                    wire:click="addToCart"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#ffde5b] px-6 py-3 w-full sm:w-auto text-xs font-bold text-[#010619] transition duration-200 hover:bg-[#f5d347] active:scale-[0.99] shadow-md shadow-[#ffde5b]/20 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                >
                    <span wire:loading.remove>ثبت نهایی و افزودن به سبد خرید</span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-[#010619]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>در حال ثبت...</span>
                    </span>
                </button>
            @endif
        </div>

        {{-- Step Counter Indicator --}}
        <div class="text-xs font-mono text-slate-400 bg-black/60 px-4 py-1.5 rounded-full border border-[#152244]">
            {{ $step }} / ۲
        </div>

        {{-- Dynamic Server-side Total Price Display --}}
        <div class="text-end">
            <span class="text-xs text-slate-400 block">مبلغ قابل پرداخت:</span>
            <span class="text-lg font-black text-[#ffde5b] tracking-tight">
                {{ number_format($totalPrice) }} <span class="text-xs font-normal text-slate-400">تومان</span>
            </span>
        </div>
    </footer>
</div>