<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8" dir="rtl">
    @php
        $steps = ['سایز چیپ', 'انتخاب طرح', 'رنگ طرح', 'پیش‌نمایش', 'پشت کارت'];
    @endphp

    {{-- Steps Indicator --}}
    <div class="mb-4 sm:mb-8">
        <div class="flex items-center justify-center gap-1 sm:gap-2">
            @foreach($steps as $index => $label)
                <div class="flex items-center gap-1 sm:gap-2">
                    <button
                        wire:click="goToStep({{ $index + 1 }})"
                        class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold transition-all
                            {{ $step > $index + 1 ? 'bg-green-500 text-white' : ($step === $index + 1 ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-500') }}"
                        {{ $step <= $index + 1 ? '' : 'cursor-pointer hover:bg-green-600' }}
                    >
                        @if($step > $index + 1)
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </button>
                    @if($index < count($steps) - 1)
                        <div class="hidden sm:block w-6 h-0.5 {{ $step > $index + 1 ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="text-center mt-2 sm:mt-3">
            <span class="text-xs sm:text-sm font-medium text-gray-600">{{ $steps[$step - 1] }}</span>
        </div>
    </div>

    {{-- MOBILE: Stacked layout --}}
    <div class="lg:hidden">
        {{-- Mobile Preview --}}
        @if($step <= 4)
            <div class="mb-6">
                <h3 class="text-xs font-medium text-gray-500 mb-2 text-center">پیش‌نمایش زنده</h3>
                <div class="relative mx-auto" style="max-width: 340px;">
                    <div class="relative w-full rounded-2xl shadow-2xl overflow-hidden border border-gray-200"
                        style="padding-top: 63.3%; background-color: #1a1a1a">
                        <div class="absolute inset-0 p-3 sm:p-5 flex flex-col justify-between">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-2">
                                    <div class="{{ $chipSize === 'large' ? 'w-12 h-8 sm:w-16 sm:h-10' : 'w-10 h-7 sm:w-12 sm:h-8' }} bg-gray-600 rounded-md border border-gray-500 flex items-center justify-center">
                                        <div class="{{ $chipSize === 'large' ? 'w-6 h-4 sm:w-8 sm:h-5' : 'w-5 h-3 sm:w-6 sm:h-4' }} bg-gray-500 rounded-sm"></div>
                                    </div>
                                </div>
                                <div class="text-white/40 text-[10px] sm:text-xs font-medium">FUEL</div>
                            </div>
                            @if($this->selectedDesignImage?->image_path)
                                <div class="absolute inset-0 flex items-center justify-center p-6 sm:p-8">
                                    <img src="{{ asset('storage/' . $this->selectedDesignImage->image_path) }}" alt=""
                                        class="max-w-full max-h-full object-contain mix-blend-screen opacity-80">
                                </div>
                            @endif
                            <div class="text-white/60 text-center text-[10px] sm:text-xs">کارت سوخت فلزی</div>
                        </div>
                    </div>
                    @if($this->selectedCardType)
                        <div class="mt-3 text-center">
                            <span class="text-xl sm:text-2xl font-bold text-gray-900">{{ number_format($this->selectedCardType->base_price) }}</span>
                            <span class="text-xs sm:text-sm text-gray-500 mr-1">تومان</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($step === 5)
            <div class="mb-6">
                <h3 class="text-xs font-medium text-gray-500 mb-2 text-center">پیش‌نمایش پشت کارت</h3>
                <div class="relative mx-auto" style="max-width: 340px;">
                    <div class="relative w-full rounded-2xl shadow-2xl overflow-hidden border border-gray-200 bg-gray-100"
                        style="padding-top: 63.3%;">
                        <div class="absolute inset-0 bg-gradient-to-b from-gray-200 to-gray-300 p-[6%]">
                            <div class="w-full h-full bg-white rounded border border-gray-300 flex flex-col justify-between p-[5%]">
                                <div class="text-center text-[9px] sm:text-[11px] font-bold text-gray-800 border-b-2 border-gray-400 pb-1.5 sm:pb-2">
                                    اطلاعات کارت سوخت
                                </div>
                                <div class="flex-1 flex flex-col justify-center space-y-[2%] sm:space-y-[3%]">
                                    <div class="grid grid-cols-[38%_1fr] border-b border-gray-200 pb-1.5 sm:pb-2">
                                        <span class="text-gray-500 text-[8px] sm:text-[10px]">نام مالک:</span>
                                        <span class="font-medium text-gray-800 text-[9px] sm:text-[11px]" dir="rtl">{{ $ownerName ?: '---' }}</span>
                                    </div>
                                    <div class="grid grid-cols-[38%_1fr] border-b border-gray-200 pb-1.5 sm:pb-2">
                                        <span class="text-gray-500 text-[8px] sm:text-[10px]">مدل خودرو:</span>
                                        <span class="font-medium text-gray-800 text-[9px] sm:text-[11px]" dir="rtl">{{ $carModel ?: '---' }}</span>
                                    </div>
                                    <div class="grid grid-cols-[38%_1fr] border-b border-gray-200 pb-1.5 sm:pb-2">
                                        <span class="text-gray-500 text-[8px] sm:text-[10px]">شماره VIN:</span>
                                        <span class="font-medium text-gray-800 text-[9px] sm:text-[11px] font-mono" dir="ltr">{{ $vinNumber ?: '---' }}</span>
                                    </div>
                                    <div class="grid grid-cols-[38%_1fr] border-b border-gray-200 pb-1.5 sm:pb-2">
                                        <span class="text-gray-500 text-[8px] sm:text-[10px]">سیستم:</span>
                                        <span class="font-medium text-gray-800 text-[9px] sm:text-[11px] font-mono" dir="ltr">{{ $sysNumber ?: '---' }}</span>
                                    </div>
                                    @if($plateNumber)
                                        <div class="grid grid-cols-[38%_1fr] pb-1 sm:pb-2">
                                            <span class="text-gray-500 text-[8px] sm:text-[10px]">پلاک:</span>
                                            <span class="font-medium text-gray-800 text-[9px] sm:text-[11px]" dir="rtl">{{ $plateNumber }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="text-center text-[6px] sm:text-[8px] text-gray-400 border-t border-gray-300 pt-0.5 sm:pt-1 mt-auto">
                                    {{ $chipSize === 'large' ? 'چیپ بزرگ' : 'چیپ کوچک' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- DESKTOP: Side by side layout --}}
    <div class="hidden lg:grid lg:grid-cols-5 gap-8">
        <div class="lg:col-span-3">
            @if($step === 1)
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">سایز چیپ کارت سوخت را انتخاب کنید</h2>
                    <p class="text-gray-500 mb-6">کارت سوخت فقط با رنگ مشکی موجود است</p>
                    <div class="grid sm:grid-cols-2 gap-4 max-w-lg">
                        <button wire:click="selectChipSize('small')"
                            class="group p-6 bg-white rounded-2xl border-2 {{ $chipSize === 'small' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200' }} hover:border-yellow-500 transition text-center">
                            <div class="w-20 h-14 bg-gray-900 rounded-lg mx-auto mb-4 flex items-center justify-center relative">
                                <div class="w-6 h-4 bg-gray-600 rounded-sm"></div>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">چیپ کوچک</h3>
                            <p class="text-sm text-gray-500">چیپ استاندارد</p>
                        </button>
                        <button wire:click="selectChipSize('large')"
                            class="group p-6 bg-white rounded-2xl border-2 {{ $chipSize === 'large' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200' }} hover:border-yellow-500 transition text-center">
                            <div class="w-20 h-14 bg-gray-900 rounded-lg mx-auto mb-4 flex items-center justify-center relative">
                                <div class="w-10 h-6 bg-gray-600 rounded-sm"></div>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">چیپ بزرگ</h3>
                            <p class="text-sm text-gray-500">چیپ بزرگ‌تر</p>
                        </button>
                    </div>
                </div>
            @elseif($step === 2)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">طرح کارت سوخت را انتخاب کنید</h2>
                    <p class="text-gray-500 mb-6">طرح‌های ماشینی در اولویت نمایش هستند</p>
                    <div class="mb-4">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجوی طرح..."
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    </div>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($this->designs as $design)
                            <button wire:click="selectDesign({{ $design->id }})"
                                class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-yellow-500 overflow-hidden transition">
                                <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
                                    @if($design->image_path)
                                        <img src="{{ asset('storage/' . $design->image_path) }}" alt="{{ $design->name }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition">
                                    @else
                                        <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="p-3">
                                    <span class="text-sm font-medium text-gray-900">{{ $design->name }}</span>
                                    @php
                                        $catName = $design->groupDesign?->cateDesign?->name ?? '';
                                    @endphp
                                    @if($catName === 'ماشین‌ها')
                                        <span class="mr-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">ماشینی</span>
                                    @endif
                                </div>
                            </button>
                        @empty
                            <p class="col-span-full text-center text-gray-400 py-8">طرحی موجود نیست</p>
                        @endforelse
                    </div>
                </div>
            @elseif($step === 3)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">رنگ طرح را انتخاب کنید</h2>
                    <p class="text-gray-500 mb-6">رنگ‌های ناسازگار با کارت مشکی حذف شده‌اند</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                        @forelse($this->designImages as $designImage)
                            <button wire:click="selectDesignImage({{ $designImage->id }})"
                                class="group p-4 bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 transition text-center">
                                <div class="w-12 h-12 rounded-full mx-auto mb-2 border-2 border-gray-200 shadow-inner"
                                    style="background-color: {{ $designImage->color->color_code }}"></div>
                                <span class="text-sm font-medium text-gray-700">{{ $designImage->color->name }}</span>
                                @if($designImage->image_path)
                                    <div class="mt-2 w-full aspect-square rounded-lg overflow-hidden bg-gray-100">
                                        <img src="{{ asset('storage/' . $designImage->image_path) }}" alt="" class="w-full h-full object-cover">
                                    </div>
                                @endif
                            </button>
                        @empty
                            <p class="col-span-full text-center text-gray-400 py-8">رنگی موجود نیست</p>
                        @endforelse
                    </div>
                </div>
            @elseif($step === 4)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">پیش‌نمایش نهایی</h2>
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">سایز چیپ:</span>
                                <span class="font-medium">{{ $this->chipSize === 'large' ? 'چیپ بزرگ' : 'چیپ کوچک' }}</span>
                            </div>
                            @if($this->selectedDesignImage)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">طرح:</span>
                                    <span class="font-medium">{{ $this->selectedDesignImage?->design?->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">رنگ طرح:</span>
                                    <span class="font-medium">{{ $this->selectedDesignImage?->color?->name }}</span>
                                </div>
                            @endif
                            @if($this->selectedCardType)
                                <div class="flex justify-between border-t pt-3 mt-3">
                                    <span class="text-gray-500">قیمت پایه:</span>
                                    <span class="font-bold text-yellow-500">{{ number_format($this->selectedCardType->base_price) }} تومان</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <button wire:click="goToBackForm"
                        class="w-full bg-yellow-500 text-white py-3 rounded-xl font-bold text-lg hover:bg-yellow-600 transition">
                        طراحی پشت کارت
                    </button>
                </div>
            @elseif($step === 5)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">اطلاعات پشت کارت سوخت</h2>
                    <p class="text-gray-500 mb-6">اطلاعات خودرو را وارد کنید</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">نام مالک <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live="ownerName" placeholder="نام کامل مالک خودرو"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('ownerName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">مدل خودرو <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live="carModel" placeholder="مثال: پراید ۱۳۲"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('carModel') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">شماره VIN <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live="vinNumber" placeholder="۱۷ کاراکتر" maxlength="17"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono" dir="ltr">
                            @error('vinNumber') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">شماره سیستم <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live="sysNumber"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition" dir="ltr">
                            @error('sysNumber') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">شماره پلاک (اختیاری)</label>
                            <input type="text" wire:model.live="plateNumber" placeholder="مثال: ۱۲ الف ۳۴۵"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        </div>
                    </div>
                    <div class="mt-6">
                        <button wire:click="saveCard"
                            class="w-full bg-green-600 text-white py-3 rounded-xl font-bold text-lg hover:bg-green-700 transition disabled:opacity-50"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveCard">ذخیره و افزودن به سبد خرید</span>
                            <span wire:loading wire:target="saveCard">در حال ذخیره...</span>
                        </button>
                    </div>
                    @if($saved)
                        <div class="mt-4 bg-green-50 text-green-600 text-sm p-3 rounded-lg text-center">
                            اطلاعات با موفقیت ذخیره شد! <a href="{{ route('home') }}" class="underline font-bold">بازگشت به خانه</a>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="lg:col-span-2">
            <div class="sticky top-8">
                @if($step <= 4)
                    <h3 class="text-sm font-medium text-gray-500 mb-3 text-center">پیش‌نمایش زنده</h3>
                    <div class="relative mx-auto" style="max-width: 340px;">
                        <div class="relative w-full rounded-2xl shadow-2xl overflow-hidden border border-gray-200"
                            style="padding-top: 63.3%; background-color: #1a1a1a">
                            <div class="absolute inset-0 p-5 flex flex-col justify-between">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center gap-2">
                                        <div class="{{ $chipSize === 'large' ? 'w-16 h-10' : 'w-12 h-8' }} bg-gray-600 rounded-md border border-gray-500 flex items-center justify-center">
                                            <div class="{{ $chipSize === 'large' ? 'w-8 h-5' : 'w-6 h-4' }} bg-gray-500 rounded-sm"></div>
                                        </div>
                                    </div>
                                    <div class="text-white/40 text-xs font-medium">FUEL</div>
                                </div>
                                @if($this->selectedDesignImage?->image_path)
                                    <div class="absolute inset-0 flex items-center justify-center p-8">
                                        <img src="{{ asset('storage/' . $this->selectedDesignImage->image_path) }}" alt=""
                                            class="max-w-full max-h-full object-contain mix-blend-screen opacity-80">
                                    </div>
                                @endif
                                <div class="text-white/60 text-center text-xs">کارت سوخت فلزی</div>
                            </div>
                        </div>
                        @if($this->selectedCardType)
                            <div class="mt-4 text-center">
                                <span class="text-2xl font-bold text-gray-900">{{ number_format($this->selectedCardType->base_price) }}</span>
                                <span class="text-sm text-gray-500 mr-1">تومان</span>
                            </div>
                        @endif
                    </div>
                @elseif($step === 5)
                    <h3 class="text-sm font-medium text-gray-500 mb-3 text-center">پیش‌نمایش پشت کارت</h3>
                    <div class="relative mx-auto" style="max-width: 340px;">
                        <div class="relative w-full rounded-2xl shadow-2xl overflow-hidden border border-gray-200 bg-gray-100"
                            style="padding-top: 63.3%;">
                            <div class="absolute inset-0 bg-gradient-to-b from-gray-200 to-gray-300 p-[6%]">
                                <div class="w-full h-full bg-white rounded border border-gray-300 flex flex-col justify-between p-[5%]">
                                    <div class="text-center text-[11px] font-bold text-gray-800 border-b-2 border-gray-400 pb-2">
                                        اطلاعات کارت سوخت
                                    </div>
                                    <div class="flex-1 flex flex-col justify-center space-y-[3%]">
                                        <div class="grid grid-cols-[40%_1fr] border-b border-gray-200 pb-2">
                                            <span class="text-gray-500 text-[10px]">نام مالک:</span>
                                            <span class="font-medium text-gray-800 text-[11px]" dir="rtl">{{ $ownerName ?: '---' }}</span>
                                        </div>
                                        <div class="grid grid-cols-[40%_1fr] border-b border-gray-200 pb-2">
                                            <span class="text-gray-500 text-[10px]">مدل خودرو:</span>
                                            <span class="font-medium text-gray-800 text-[11px]" dir="rtl">{{ $carModel ?: '---' }}</span>
                                        </div>
                                        <div class="grid grid-cols-[40%_1fr] border-b border-gray-200 pb-2">
                                            <span class="text-gray-500 text-[10px]">شماره VIN:</span>
                                            <span class="font-medium text-gray-800 text-[11px] font-mono" dir="ltr">{{ $vinNumber ?: '---' }}</span>
                                        </div>
                                        <div class="grid grid-cols-[40%_1fr] border-b border-gray-200 pb-2">
                                            <span class="text-gray-500 text-[10px]">سیستم:</span>
                                            <span class="font-medium text-gray-800 text-[11px] font-mono" dir="ltr">{{ $sysNumber ?: '---' }}</span>
                                        </div>
                                        @if($plateNumber)
                                            <div class="grid grid-cols-[40%_1fr] pb-2">
                                                <span class="text-gray-500 text-[10px]">پلاک:</span>
                                                <span class="font-medium text-gray-800 text-[11px]" dir="rtl">{{ $plateNumber }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-center text-[8px] text-gray-400 border-t border-gray-300 pt-1 mt-auto">
                                        {{ $chipSize === 'large' ? 'چیپ بزرگ' : 'چیپ کوچک' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MOBILE: Selection panel --}}
    <div class="lg:hidden">
        @if($step === 1)
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">سایز چیپ کارت سوخت را انتخاب کنید</h2>
                <p class="text-sm text-gray-500 mb-4">کارت سوخت فقط با رنگ مشکی موجود است</p>
                <div class="grid grid-cols-2 gap-3">
                    <button wire:click="selectChipSize('small')"
                        class="group p-4 sm:p-6 bg-white rounded-xl sm:rounded-2xl border-2 {{ $chipSize === 'small' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200' }} hover:border-yellow-500 transition text-center">
                        <div class="w-16 h-10 sm:w-20 sm:h-14 bg-gray-900 rounded-lg mx-auto mb-3 sm:mb-4 flex items-center justify-center relative">
                            <div class="w-5 h-3 sm:w-6 sm:h-4 bg-gray-600 rounded-sm"></div>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">چیپ کوچک</h3>
                        <p class="text-xs sm:text-sm text-gray-500">چیپ استاندارد</p>
                    </button>
                    <button wire:click="selectChipSize('large')"
                        class="group p-4 sm:p-6 bg-white rounded-xl sm:rounded-2xl border-2 {{ $chipSize === 'large' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200' }} hover:border-yellow-500 transition text-center">
                        <div class="w-16 h-10 sm:w-20 sm:h-14 bg-gray-900 rounded-lg mx-auto mb-3 sm:mb-4 flex items-center justify-center relative">
                            <div class="w-8 h-5 sm:w-10 sm:h-6 bg-gray-600 rounded-sm"></div>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">چیپ بزرگ</h3>
                        <p class="text-xs sm:text-sm text-gray-500">چیپ بزرگ‌تر</p>
                    </button>
                </div>
            </div>

        @elseif($step === 2)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">طرح کارت سوخت را انتخاب کنید</h2>
                <p class="text-sm text-gray-500 mb-4">طرح‌های ماشینی در اولویت نمایش هستند</p>
                <div class="mb-3">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجوی طرح..."
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @forelse($this->designs as $design)
                        <button wire:click="selectDesign({{ $design->id }})"
                            class="group bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 overflow-hidden transition">
                            <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
                                @if($design->image_path)
                                    <img src="{{ asset('storage/' . $design->image_path) }}" alt="{{ $design->name }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition">
                                @else
                                    <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="p-2">
                                <span class="text-xs sm:text-sm font-medium text-gray-900">{{ $design->name }}</span>
                                @php
                                    $catName = $design->groupDesign?->cateDesign?->name ?? '';
                                @endphp
                                @if($catName === 'ماشین‌ها')
                                    <span class="mr-1 text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-full">ماشینی</span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <p class="col-span-full text-center text-gray-400 py-8">طرحی موجود نیست</p>
                    @endforelse
                </div>
            </div>

        @elseif($step === 3)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">رنگ طرح را انتخاب کنید</h2>
                <p class="text-xs text-gray-500 mb-4">رنگ‌های ناسازگار حذف شده‌اند</p>
                <div class="grid grid-cols-3 gap-2">
                    @forelse($this->designImages as $designImage)
                        <button wire:click="selectDesignImage({{ $designImage->id }})"
                            class="group p-3 bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 transition text-center">
                            <div class="w-10 h-10 rounded-full mx-auto mb-1.5 border-2 border-gray-200 shadow-inner"
                                style="background-color: {{ $designImage->color->color_code }}"></div>
                            <span class="text-xs font-medium text-gray-700">{{ $designImage->color->name }}</span>
                            @if($designImage->image_path)
                                <div class="mt-1.5 w-full aspect-square rounded-lg overflow-hidden bg-gray-100">
                                    <img src="{{ asset('storage/' . $designImage->image_path) }}" alt="" class="w-full h-full object-cover">
                                </div>
                            @endif
                        </button>
                    @empty
                        <p class="col-span-full text-center text-gray-400 py-8">رنگی موجود نیست</p>
                    @endforelse
                </div>
            </div>

        @elseif($step === 4)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">پیش‌نمایش نهایی</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">سایز چیپ:</span>
                            <span class="font-medium">{{ $this->chipSize === 'large' ? 'چیپ بزرگ' : 'چیپ کوچک' }}</span>
                        </div>
                        @if($this->selectedDesignImage)
                            <div class="flex justify-between">
                                <span class="text-gray-500">طرح:</span>
                                <span class="font-medium">{{ $this->selectedDesignImage?->design?->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">رنگ طرح:</span>
                                <span class="font-medium">{{ $this->selectedDesignImage?->color?->name }}</span>
                            </div>
                        @endif
                        @if($this->selectedCardType)
                            <div class="flex justify-between border-t pt-2 mt-2">
                                <span class="text-gray-500">قیمت پایه:</span>
                                <span class="font-bold text-yellow-500">{{ number_format($this->selectedCardType->base_price) }} تومان</span>
                            </div>
                        @endif
                    </div>
                </div>
                <button wire:click="goToBackForm"
                    class="w-full bg-yellow-500 text-white py-3 rounded-xl font-bold text-lg hover:bg-yellow-600 transition">
                    طراحی پشت کارت
                </button>
            </div>

        @elseif($step === 5)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">اطلاعات پشت کارت سوخت</h2>
                <p class="text-sm text-gray-500 mb-4">اطلاعات خودرو را وارد کنید</p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نام مالک <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="ownerName" placeholder="نام کامل مالک خودرو"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                        @error('ownerName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">مدل خودرو <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="carModel" placeholder="مثال: پراید ۱۳۲"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                        @error('carModel') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شماره VIN <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="vinNumber" placeholder="۱۷ کاراکتر" maxlength="17"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-sm" dir="ltr">
                        @error('vinNumber') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شماره سیستم <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="sysNumber"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-sm" dir="ltr">
                        @error('sysNumber') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شماره پلاک (اختیاری)</label>
                        <input type="text" wire:model.live="plateNumber" placeholder="مثال: ۱۲ الف ۳۴۵"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                    </div>
                </div>
                <div class="mt-5">
                    <button wire:click="saveCard"
                        class="w-full bg-green-600 text-white py-3 rounded-xl font-bold text-lg hover:bg-green-700 transition disabled:opacity-50"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveCard">ذخیره و افزودن به سبد خرید</span>
                        <span wire:loading wire:target="saveCard">در حال ذخیره...</span>
                    </button>
                </div>
                @if($saved)
                    <div class="mt-3 bg-green-50 text-green-600 text-sm p-3 rounded-lg text-center">
                        اطلاعات با موفقیت ذخیره شد! <a href="{{ route('home') }}" class="underline font-bold">بازگشت به خانه</a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
