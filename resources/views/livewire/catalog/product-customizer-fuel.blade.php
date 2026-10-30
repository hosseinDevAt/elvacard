{{-- FUEL CARD WORKSPACE: Step 2 — vehicle & fuel system back specifications. --}}
<div class="space-y-6">
    <div>
        <h2 class="text-base font-bold text-[#010619] mb-1">۲. مشخصات کارت سوخت</h2>
        <p class="text-xs text-slate-500">مشخصات مالک خودرو، سامانه سوخت و سایز چیپ را برای حکاکی روی پشت کارت وارد کنید.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Iranian Plate Structured Widget --}}
        <div class="space-y-1.5 sm:col-span-2">
            <div class="flex items-center justify-between">
                <label class="block text-xs font-bold text-slate-700">
                    پلاک ایران
                </label>
                <span class="rounded bg-[#F3F1EB] px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-[#DDD9CF]">اختیاری</span>
            </div>
            <div class="flex items-center justify-center p-2 rounded-2xl bg-[#F8F7F3] border border-[#DDD9CF] focus-within:border-[#ffde5b] transition">
                <div class="inline-flex items-center rounded-xl border border-slate-700 bg-white text-slate-950 shadow-md overflow-hidden h-12 w-full max-w-sm" dir="ltr" style="direction: ltr !important;">
                    {{-- Iran Flag Stripe on Left --}}
                    <div class="bg-[#003399] text-white px-2.5 h-full flex flex-col justify-center items-center text-[8px] font-mono leading-none border-r border-slate-300 shrink-0 select-none">
                        <span class="text-[9px]">🇮🇷</span>
                        <span class="tracking-tighter font-extrabold mt-0.5">I.R.</span>
                        <span class="tracking-tighter text-[6px]">IRAN</span>
                    </div>

                    {{-- Part 1: 2 Digits --}}
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="2"
                        wire:model.live.debounce.150ms="fuelCard.plate_part1"
                        placeholder="۱۲"
                        class="w-12 text-center text-base sm:text-lg font-black text-slate-900 bg-transparent focus:outline-none focus:bg-amber-50 h-full border-r border-slate-200"
                    >

                    {{-- Letter Select --}}
                    <div class="relative flex-1 min-w-[50px] h-full flex items-center border-r border-slate-200 bg-slate-50/50">
                        <select
                            wire:model.live="fuelCard.plate_letter"
                            class="w-full text-center text-sm sm:text-base font-black text-slate-900 bg-transparent focus:outline-none focus:bg-amber-50 h-full appearance-none cursor-pointer py-0 px-1"
                        >
                            <option value="">حرف</option>
                            @foreach (['الف', 'ب', 'پ', 'ت', 'ث', 'ج', 'د', 'ز', 'س', 'ش', 'ص', 'ط', 'ع', 'ف', 'ق', 'ک', 'گ', 'ل', 'م', 'ن', 'و', 'ه', 'ی', 'ژ'] as $ltr)
                                <option value="{{ $ltr }}">{{ $ltr }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Part 2: 3 Digits --}}
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="3"
                        wire:model.live.debounce.150ms="fuelCard.plate_part2"
                        placeholder="۳۴۵"
                        class="w-16 text-center text-base sm:text-lg font-black text-slate-900 bg-transparent focus:outline-none focus:bg-amber-50 h-full border-r border-slate-300"
                    >

                    {{-- Iran Code on Right --}}
                    <div class="w-16 h-full flex flex-col justify-center items-center bg-slate-100/90 text-slate-900 shrink-0">
                        <span class="text-[9px] font-bold text-slate-500 leading-none mt-0.5">ایران</span>
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="2"
                            wire:model.live.debounce.150ms="fuelCard.plate_iran"
                            placeholder="۱۱"
                            class="w-full text-center text-sm sm:text-base font-black text-slate-900 bg-transparent focus:outline-none focus:bg-amber-50 h-7"
                        >
                    </div>
                </div>
            </div>
            @error('fuelCard.plate_number')
                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Owner Name --}}
        <div class="space-y-1.5 sm:col-span-2">
            <div class="flex items-center justify-between">
                <label for="fuel_owner_name" class="block text-xs font-bold text-slate-700">
                    نام مالک کارت / خودرو <span class="text-[#8A6A00]">*</span>
                </label>
                <span class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800 border border-amber-200">الزامی</span>
            </div>
            <input
                id="fuel_owner_name"
                type="text"
                autocomplete="off"
                maxlength="100"
                wire:model.live.debounce.150ms="fuelCard.owner_name"
                placeholder="مثال: علی رضایی"
                class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]"
            >
            @error('fuelCard.owner_name')
                <p class="text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Phone Number --}}
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="fuel_phone_number" class="block text-xs font-bold text-slate-700">
                    شماره تلفن همراه <span class="text-[#8A6A00]">*</span>
                </label>
                <span class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800 border border-amber-200">الزامی</span>
            </div>
            <input
                id="fuel_phone_number"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                maxlength="11"
                dir="ltr"
                wire:model.live.debounce.150ms="fuelCard.phone_number"
                placeholder="09123456789"
                class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] font-mono placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b] text-start"
                style="direction: ltr; text-align: start;"
            >
            @error('fuelCard.phone_number')
                <p class="text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Vehicle Type --}}
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="fuel_vehicle_type" class="block text-xs font-bold text-slate-700">
                    نوع وسیله نقلیه <span class="text-[#8A6A00]">*</span>
                </label>
                <span class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800 border border-amber-200">الزامی</span>
            </div>
            <div class="relative">
                <select
                    id="fuel_vehicle_type"
                    wire:model.live="fuelCard.vehicle_type"
                    class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b] cursor-pointer appearance-none"
                >
                    <option value="" class="bg-white text-slate-500">انتخاب کنید...</option>
                    @foreach (\App\Services\FuelCard\FuelCardCustomization::VALID_VEHICLE_TYPES as $vt)
                        <option value="{{ $vt }}" class="bg-white text-[#010619]">{{ $vt }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-3 text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
            @error('fuelCard.vehicle_type')
                <p class="text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Vehicle Model --}}
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="fuel_vehicle_model" class="block text-xs font-bold text-slate-700">
                    مدل خودرو
                </label>
                <span class="rounded bg-[#F3F1EB] px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-[#DDD9CF]">اختیاری</span>
            </div>
            <input
                id="fuel_vehicle_model"
                type="text"
                autocomplete="off"
                maxlength="100"
                wire:model.live.debounce.150ms="fuelCard.vehicle_model"
                placeholder="ولوو اف اچ 12"
                class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]"
            >
            @error('fuelCard.vehicle_model')
                <p class="text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Oil Serial Number --}}
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="fuel_oil_serial_number" class="block text-xs font-bold text-slate-700">
                    شماره سریال شرکت نفت
                </label>
                <span class="rounded bg-[#F3F1EB] px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-[#DDD9CF]">اختیاری</span>
            </div>
            <input
                id="fuel_oil_serial_number"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                maxlength="50"
                dir="ltr"
                wire:model.live.debounce.150ms="fuelCard.oil_serial_number"
                placeholder="7744330•••••"
                class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] font-mono placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b] text-start"
                style="direction: ltr; text-align: start;"
            >
            @error('fuelCard.oil_serial_number')
                <p class="text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- VIN (Chassis Number) --}}
        <div class="space-y-1.5 sm:col-span-2">
            <div class="flex items-center justify-between">
                <label for="fuel_vin" class="block text-xs font-bold text-slate-700">
                    شماره شاسی (VIN)
                </label>
                <span class="rounded bg-[#F3F1EB] px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-[#DDD9CF]">اختیاری</span>
            </div>
            <input
                id="fuel_vin"
                type="text"
                inputmode="text"
                autocomplete="off"
                maxlength="17"
                wire:model.live.debounce.150ms="fuelCard.vin"
                placeholder="IRV... (۱۷ کاراکتر)"
                oninput="this.value = this.value.toUpperCase().replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^A-Z0-9]/g, '').slice(0, 17)"
                class="w-full rounded-xl border border-[#DDD9CF] bg-white px-4 py-2.5 text-sm text-[#010619] font-mono placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]"
                style="direction: ltr; text-align: start;"
            >
            @error('fuelCard.vin')
                <p class="text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mandatory Chip Size Selection (FULLY PROTECTED) --}}
        <div class="space-y-2 sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700">
                سایز چیپ کارت سوخت <span class="text-[#8A6A00]">* (الزامی)</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer rounded-xl border p-3 flex items-center gap-3 transition {{ $fuelCard->chip_info === 'small' ? 'border-[#ffde5b] bg-[#FFFBEB] text-[#010619] shadow-sm' : 'border-[#DDD9CF] bg-white text-slate-600 hover:border-slate-400 hover:bg-[#F8F7F3]' }}">
                    <input
                        type="radio"
                        name="fuel_chip_size"
                        value="small"
                        wire:model.live="fuelCard.chip_info"
                        class="text-[#ffde5b] focus:ring-[#ffde5b] h-4 w-4 border-slate-300 bg-white"
                    >
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-[#010619]">چیپ کوچک (Small)</span>
                        <span class="text-[10px] text-slate-500">ابعاد کوچک استاندارد</span>
                    </div>
                </label>

                <label class="cursor-pointer rounded-xl border p-3 flex items-center gap-3 transition {{ $fuelCard->chip_info === 'large' ? 'border-[#ffde5b] bg-[#FFFBEB] text-[#010619] shadow-sm' : 'border-[#DDD9CF] bg-white text-slate-600 hover:border-slate-400 hover:bg-[#F8F7F3]' }}">
                    <input
                        type="radio"
                        name="fuel_chip_size"
                        value="large"
                        wire:model.live="fuelCard.chip_info"
                        class="text-[#ffde5b] focus:ring-[#ffde5b] h-4 w-4 border-slate-300 bg-white"
                    >
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-[#010619]">چیپ بزرگ (Large)</span>
                        <span class="text-[10px] text-slate-500">ابعاد بزرگ قدیمی/خاص</span>
                    </div>
                </label>
            </div>
            @error('fuelCard.chip_info')
                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Notice Box --}}
    <div class="rounded-2xl border border-[#ffde5b] bg-[#FFFBEB] p-4 text-xs text-slate-700 leading-relaxed flex items-start gap-2.5">
        <svg class="h-5 w-5 shrink-0 text-[#8A6A00] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>توجه: انتخاب سایز چیپ کارت سوخت الزامی است. اطلاعات واردشده دقیقاً مطابق دستور شما روی کارت حکاکی می‌شود.</span>
    </div>
</div>