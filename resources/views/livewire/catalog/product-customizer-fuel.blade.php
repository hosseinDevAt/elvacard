{{-- FUEL CARD WORKSPACE: Step 2 — vehicle & fuel system back specifications. --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-bold text-white mb-1">۲. مشخصات کارت سوخت</h2>
        <p class="text-xs text-gray-400">مشخصات مالک خودرو، سامانه سوخت و سایز چیپ را برای حکاکی روی پشت کارت وارد کنید.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Owner Name --}}
        <div class="space-y-1.5 sm:col-span-2">
            <label for="fuel_owner_name" class="block text-xs font-bold text-gray-300">
                نام مالک کارت / خودرو
            </label>
            <input
                id="fuel_owner_name"
                type="text"
                autocomplete="off"
                maxlength="100"
                wire:model.live.debounce.150ms="fuelCard.owner_name"
                placeholder="مثال: علی رضایی"
                class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
            >
            @error('fuelCard.owner_name')
                <p class="text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Car Information --}}
        <div class="space-y-1.5 sm:col-span-2">
            <label for="fuel_car_info" class="block text-xs font-bold text-gray-300">
                اطلاعات خودرو
            </label>
            <input
                id="fuel_car_info"
                type="text"
                autocomplete="off"
                maxlength="255"
                wire:model.live.debounce.150ms="fuelCard.car_info"
                placeholder="مثال: خودروی سواری، ۲۰۶، مدل ۱۴۰۰"
                class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
            >
            @error('fuelCard.car_info')
                <p class="text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- VIN (Chassis Number) --}}
        <div class="space-y-1.5">
            <label for="fuel_vin" class="block text-xs font-bold text-gray-300">
                شماره شاسی (VIN)
            </label>
            <input
                id="fuel_vin"
                type="text"
                inputmode="text"
                autocomplete="off"
                maxlength="17"
                wire:model.live.debounce.150ms="fuelCard.vin"
                placeholder="IRV... (۱۷ کاراکتر)"
                oninput="this.value = this.value.toUpperCase().replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^A-Z0-9]/g, '').slice(0, 17)"
                class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white font-mono placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                style="direction: ltr; text-align: start;"
            >
            @error('fuelCard.vin')
                <p class="text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Plate Number --}}
        <div class="space-y-1.5">
            <label for="fuel_plate_number" class="block text-xs font-bold text-gray-300">
                شماره پلاک
            </label>
            <input
                id="fuel_plate_number"
                type="text"
                autocomplete="off"
                maxlength="20"
                wire:model.live.debounce.150ms="fuelCard.plate_number"
                placeholder="مثال: ۱۲ م ۳۴۵ ایران"
                class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                style="direction: ltr; text-align: start;"
            >
            @error('fuelCard.plate_number')
                <p class="text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Fuel System Name --}}
        <div class="space-y-1.5">
            <label for="fuel_system_name" class="block text-xs font-bold text-gray-300">
                سامانه سوخت
            </label>
            <input
                id="fuel_system_name"
                type="text"
                autocomplete="off"
                maxlength="100"
                wire:model.live.debounce.150ms="fuelCard.system_name"
                placeholder="مثال: سامانه هوشمند سوخت"
                class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
            >
            @error('fuelCard.system_name')
                <p class="text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Fuel System Identifier --}}
        <div class="space-y-1.5">
            <label for="fuel_system_identifier" class="block text-xs font-bold text-gray-300">
                شناسه سامانه
            </label>
            <input
                id="fuel_system_identifier"
                type="text"
                autocomplete="off"
                maxlength="64"
                wire:model.live.debounce.150ms="fuelCard.system_identifier"
                placeholder="مثال: شناسه ثبت‌نام سوخت"
                class="w-full rounded-xl border border-gray-800 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
            >
            @error('fuelCard.system_identifier')
                <p class="text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mandatory Chip Size Selection --}}
        <div class="space-y-2 sm:col-span-2">
            <label class="block text-xs font-bold text-gray-300">
                سایز چیپ کارت سوخت <span class="text-amber-400">* (الزامی)</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer rounded-xl border p-3 flex items-center gap-3 transition {{ $fuelCard->chip_info === 'small' ? 'border-amber-500 bg-amber-500/10 text-white shadow-md' : 'border-gray-800 bg-gray-950 text-gray-400 hover:border-gray-700' }}">
                    <input
                        type="radio"
                        name="fuel_chip_size"
                        value="small"
                        wire:model.live="fuelCard.chip_info"
                        class="text-amber-500 focus:ring-amber-500 h-4 w-4 border-gray-700 bg-gray-900"
                    >
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-white">چیپ کوچک (Small)</span>
                        <span class="text-[10px] text-gray-400">ابعاد کوچک استاندارد</span>
                    </div>
                </label>

                <label class="cursor-pointer rounded-xl border p-3 flex items-center gap-3 transition {{ $fuelCard->chip_info === 'large' ? 'border-amber-500 bg-amber-500/10 text-white shadow-md' : 'border-gray-800 bg-gray-950 text-gray-400 hover:border-gray-700' }}">
                    <input
                        type="radio"
                        name="fuel_chip_size"
                        value="large"
                        wire:model.live="fuelCard.chip_info"
                        class="text-amber-500 focus:ring-amber-500 h-4 w-4 border-gray-700 bg-gray-900"
                    >
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-white">چیپ بزرگ (Large)</span>
                        <span class="text-[10px] text-gray-400">ابعاد بزرگ قدیمی/خاص</span>
                    </div>
                </label>
            </div>
            @error('fuelCard.chip_info')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Notice Box --}}
    <div class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-4 text-xs text-amber-300/90 leading-relaxed flex items-start gap-2.5">
        <svg class="h-5 w-5 shrink-0 text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>توجه: انتخاب سایز چیپ کارت سوخت الزامی است. اطلاعات واردشده دقیقاً مطابق دستور شما روی کارت حکاکی می‌شود.</span>
    </div>
</div>