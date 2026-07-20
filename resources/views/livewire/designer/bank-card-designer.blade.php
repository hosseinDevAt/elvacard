<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8" dir="rtl">
    @php
        $stepLabels = ['انتخاب رنگ', 'دسته‌بندی', 'گروه طرح', 'انتخاب طرح', 'رنگ طرح', 'پیش‌نمایش', 'پشت کارت'];
    @endphp

    {{-- Steps Indicator --}}
    <div class="mb-4 sm:mb-8">
        <div class="flex items-center justify-center gap-1 sm:gap-2">
            @foreach($stepLabels as $index => $label)
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
                    @if($index < count($stepLabels) - 1)
                        <div class="hidden sm:block w-6 h-0.5 {{ $step > $index + 1 ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="text-center mt-2 sm:mt-3">
            <span class="text-xs sm:text-sm font-medium text-gray-600">{{ $stepLabels[$step - 1] }}</span>
        </div>
    </div>

    {{-- MOBILE: Stacked layout (preview on top, form below) --}}
    <div class="lg:hidden">
        {{-- Mobile Preview --}}
        @if($step <= 6)
            <div class="mb-6">
                @php
                    $isLight = $this->isLightColor();
                    $textMain = $isLight ? 'text-gray-800' : 'text-white/80';
                    $textSub = $isLight ? 'text-gray-500' : 'text-white/40';
                    $textMuted = $isLight ? 'text-gray-600' : 'text-white/60';
                    $chipBg = $isLight ? 'bg-gray-700/20 border-gray-700/20' : 'bg-white/20 border-white/20';
                @endphp
                <h3 class="text-xs font-medium text-gray-500 mb-2 text-center">پیش‌نمایش زنده</h3>
                <div class="relative mx-auto" style="max-width: 340px;">
                    <div class="relative w-full rounded-2xl shadow-2xl overflow-hidden border {{ $isLight ? 'border-gray-300' : 'border-gray-200' }}"
                        style="padding-top: 63.3%; background-color: {{ $this->selectedColor?->color_code ?? '#1f2937' }}">
                        @if($this->selectedDesignImage?->image_path)
                            <img src="{{ asset('storage/' . $this->selectedDesignImage->image_path) }}" alt=""
                                class="absolute inset-0 w-full h-full object-contain p-4 mix-blend-multiply">
                        @endif
                        <div class="absolute inset-0 p-3 sm:p-5 flex flex-col justify-between">
                            <div class="flex justify-between items-start">
                                <div class="w-10 h-7 sm:w-12 sm:h-9 rounded-md backdrop-blur-sm border {{ $chipBg }}"></div>
                                <div class="{{ $textMuted }} text-[10px] sm:text-xs font-medium">CARD</div>
                            </div>
                            <div class="{{ $textMain }}">
                                <div class="text-sm sm:text-lg tracking-widest font-mono mb-2 sm:mb-3">
                                    **** &nbsp; **** &nbsp; **** &nbsp; ****
                                </div>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <div class="text-[8px] sm:text-[10px] {{ $textSub }} mb-0.5">CARD HOLDER</div>
                                        <div class="text-xs sm:text-sm font-medium">{{ $holderName ?: 'نام دارنده کارت' }}</div>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-[8px] sm:text-[10px] {{ $textSub }} mb-0.5">VALID THRU</div>
                                        <div class="text-xs sm:text-sm font-medium">{{ $expiryDate ?: 'MM/YY' }}</div>
                                    </div>
                                </div>
                            </div>
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
    </div>

    {{-- MOBILE: Back card preview (step 7) --}}
    <div class="lg:hidden">
        @if($step === 7)
            <div class="mb-6">
                <h3 class="text-xs font-medium text-gray-500 mb-2 text-center">پیش‌نمایش پشت کارت</h3>
                <p class="text-[10px] text-gray-400 mb-3 text-center">فیلدها را بکشید و جای آن‌ها را تغییر دهید</p>
                <div class="relative mx-auto" style="max-width: 340px;" wire:ignore
                    x-data="{
                        dragging: null,
                        cardEl: null,
                        offsetX: 0,
                        offsetY: 0,
                        scrollY: 0,
                        positions: Object.fromEntries(Object.entries({{ json_encode($fieldPositions) }}).map(([k,v]) => [k, {...v, fontSize: Number(v.fontSize)}])),
                        startDrag(event, field) {
                            event.preventDefault();
                            this.dragging = field;
                            const fieldEl = event.target.closest('.draggable-field');
                            this.cardEl = fieldEl.closest('.card-back');
                            const fRect = fieldEl.getBoundingClientRect();
                            const clientX = event.clientX || (event.touches && event.touches[0] ? event.touches[0].clientX : 0);
                            const clientY = event.clientY || (event.touches && event.touches[0] ? event.touches[0].clientY : 0);
                            this.offsetX = clientX - fRect.left;
                            this.offsetY = clientY - fRect.top;
                            if (event.type === 'touchstart') {
                                this.scrollY = window.scrollY;
                                document.documentElement.style.overflow = 'hidden';
                                document.body.style.overflow = 'hidden';
                            }
                        },
                        onDrag(event) {
                            if (!this.dragging || !this.cardEl) return;
                            event.preventDefault();
                            const rect = this.cardEl.getBoundingClientRect();
                            const clientX = event.clientX || (event.touches && event.touches[0] ? event.touches[0].clientX : 0);
                            const clientY = event.clientY || (event.touches && event.touches[0] ? event.touches[0].clientY : 0);
                            let newLeft = ((clientX - rect.left - this.offsetX) / rect.width) * 100;
                            let newTop = ((clientY - rect.top - this.offsetY) / rect.height) * 100;
                            newLeft = Math.max(0, Math.min(100 - parseFloat(this.positions[this.dragging].width), newLeft));
                            newTop = Math.max(0, Math.min(90, newTop));
                            this.positions[this.dragging].left = newLeft.toFixed(1);
                            this.positions[this.dragging].top = newTop.toFixed(1);
                        },
                        endDrag() {
                            if (this.dragging) {
                                const field = this.dragging;
                                const pos = this.positions[field];
                                $wire.call('updatePosition', field, pos.top, pos.left);
                                this.dragging = null;
                                this.cardEl = null;
                            }
                            document.documentElement.style.overflow = '';
                            document.body.style.overflow = '';
                            window.scrollTo(0, this.scrollY || 0);
                        }
                    }"
                    @mousemove.window="onDrag($event)"
                    @touchmove.window="onDrag($event)"
                    @mouseup.window="endDrag()"
                    @touchend.window="endDrag()"
                >
                        <div class="card-back relative w-full rounded-2xl shadow-2xl overflow-hidden border border-gray-200 bg-gray-100"
                            style="padding-top: 63.3%; touch-action: none;">
                            <div class="absolute inset-0 bg-gradient-to-b from-gray-200 to-gray-300">
                                <div class="absolute top-[8%] left-0 right-0 h-[12%] bg-gray-700"></div>

                                <div class="draggable-field absolute text-gray-700 font-mono tracking-wider cursor-move select-none bg-white/80 px-1.5 py-0.5 sm:px-2 sm:py-1 rounded shadow-sm"
                                    :style="`touch-action:none; top:${positions.card_number.top}%; left:${positions.card_number.left}%; width:${positions.card_number.width}%; font-size:${positions.card_number.fontSize}px;`"
                                    @mousedown="startDrag($event, 'card_number')" @touchstart.prevent="startDrag($event, 'card_number')" dir="ltr">
                                    {{ $this->getFormattedCardNumber() }}
                                </div>
                                <div class="draggable-field absolute text-gray-700 cursor-move select-none bg-white/80 px-1.5 py-0.5 sm:px-2 sm:py-1 rounded shadow-sm"
                                    :style="`touch-action:none; top:${positions.holder_name.top}%; left:${positions.holder_name.left}%; width:${positions.holder_name.width}%; font-size:${positions.holder_name.fontSize}px;`"
                                    @mousedown="startDrag($event, 'holder_name')" @touchstart.prevent="startDrag($event, 'holder_name')">
                                    {{ $holderName ?: 'نام دارنده کارت' }}
                                </div>
                                <div class="draggable-field absolute text-gray-700 font-mono cursor-move select-none bg-white/80 px-1.5 py-0.5 sm:px-2 sm:py-1 rounded shadow-sm"
                                    :style="`touch-action:none; top:${positions.expiry_date.top}%; left:${positions.expiry_date.left}%; width:${positions.expiry_date.width}%; font-size:${positions.expiry_date.fontSize}px;`"
                                    @mousedown="startDrag($event, 'expiry_date')" @touchstart.prevent="startDrag($event, 'expiry_date')" dir="ltr">
                                    {{ $expiryDate ?: 'MM/YY' }}
                                </div>
                                <div class="draggable-field absolute text-gray-700 font-mono cursor-move select-none bg-white/80 px-1.5 py-0.5 sm:px-2 sm:py-1 rounded shadow-sm"
                                    :style="`touch-action:none; top:${positions.cvv2.top}%; left:${positions.cvv2.left}%; width:${positions.cvv2.width}%; font-size:${positions.cvv2.fontSize}px;`"
                                    @mousedown="startDrag($event, 'cvv2')" @touchstart.prevent="startDrag($event, 'cvv2')" dir="ltr">
                                    {{ $cvv2 ?: '***' }}
                                </div>

                            <div class="absolute bottom-[8%] left-[5%] right-[5%] h-[6%] bg-gray-400/50 rounded flex items-center justify-center">
                                <span class="text-[6px] sm:text-[8px] text-gray-600 font-mono" dir="ltr">CVV2: {{ $cvv2 ?: '***' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Font Size Controls --}}
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @foreach(['card_number' => 'شماره کارت', 'holder_name' => 'نام دارنده', 'expiry_date' => 'تاریخ', 'cvv2' => 'CVV2'] as $field => $label)
                            <div class="flex items-center gap-2 bg-white rounded-lg border border-gray-200 p-1.5">
                                <span class="text-[10px] text-gray-500 shrink-0">{{ $label }}</span>
                                <button @click="positions.{{ $field }}.fontSize = Math.max(8, positions.{{ $field }}.fontSize - 1); $wire.updateFontSize('{{ $field }}', String(positions.{{ $field }}.fontSize))"
                                    class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-xs font-bold shrink-0">-</button>
                                <span class="text-[10px] text-gray-600 text-center min-w-[24px]" x-text="positions.{{ $field }}.fontSize + 'px'"></span>
                                <button @click="positions.{{ $field }}.fontSize = Math.min(24, positions.{{ $field }}.fontSize + 1); $wire.updateFontSize('{{ $field }}', String(positions.{{ $field }}.fontSize))"
                                    class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-xs font-bold shrink-0">+</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- DESKTOP: Side by side layout --}}
    <div class="hidden lg:grid lg:grid-cols-5 gap-8">
        {{-- Selection Panel --}}
        <div class="lg:col-span-3">
            @if($step === 1)
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">رنگ کارت بانکی را انتخاب کنید</h2>
                    <p class="text-gray-500 mb-6">رنگ فلز کارت خود را انتخاب کنید</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                        @forelse($this->availableColors as $color)
                            <button wire:click="selectColor({{ $color->id }})"
                                class="group p-4 bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 transition text-center">
                                <div class="w-14 h-14 rounded-full mx-auto mb-2 border-2 border-gray-200 shadow-inner"
                                    style="background-color: {{ $color->color_code }}"></div>
                                <span class="text-sm font-medium text-gray-700">{{ $color->name }}</span>
                            </button>
                        @empty
                            <p class="col-span-full text-center text-gray-400 py-8">رنگی موجود نیست</p>
                        @endforelse
                    </div>
                </div>

            @elseif($step === 2)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">دسته‌بندی طرح را انتخاب کنید</h2>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($this->cateDesigns as $category)
                            <button wire:click="selectCateDesign({{ $category->id }})"
                                class="p-5 bg-white rounded-2xl border-2 border-gray-200 hover:border-yellow-500 transition text-right">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $category->name }}</h3>
                                <p class="text-sm text-gray-400">{{ $category->groupDesigns->count() }} گروه طرح</p>
                            </button>
                        @empty
                            <p class="col-span-full text-center text-gray-400 py-8">دسته‌بندی‌ای موجود نیست</p>
                        @endforelse
                    </div>
                </div>

            @elseif($step === 3)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">گروه طرح را انتخاب کنید</h2>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($this->groupDesigns as $group)
                            <button wire:click="selectGroupDesign({{ $group->id }})"
                                class="p-5 bg-white rounded-2xl border-2 border-gray-200 hover:border-yellow-500 transition text-right">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $group->name }}</h3>
                                <p class="text-sm text-gray-400">{{ $group->designs->count() }} طرح</p>
                            </button>
                        @empty
                            <p class="col-span-full text-center text-gray-400 py-8">گروه‌ای موجود نیست</p>
                        @endforelse
                    </div>
                </div>

            @elseif($step === 4)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">طرح را انتخاب کنید</h2>
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
                                </div>
                            </button>
                        @empty
                            <p class="col-span-full text-center text-gray-400 py-8">طرحی موجود نیست</p>
                        @endforelse
                    </div>
                </div>

            @elseif($step === 5)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">رنگ طرح را انتخاب کنید</h2>
                    <p class="text-gray-500 mb-6">رنگ‌های ناسازگار با رنگ کارت انتخابی حذف شده‌اند</p>
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

            @elseif($step === 6)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">پیش‌نمایش نهایی</h2>
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">رنگ کارت:</span>
                                <span class="font-medium">{{ $this->selectedColor?->name }}</span>
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

            @elseif($step === 7)
                <div>
                    <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        بازگشت
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">اطلاعات پشت کارت</h2>
                    <p class="text-gray-500 mb-6">فیلدها را پر کنید و جای آن‌ها را روی کارت تنظیم کنید</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">نام دارنده کارت <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live="holderName" placeholder="نام کامل"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('holderName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">شماره کارت <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live="cardNumber" placeholder="0000 0000 0000 0000" maxlength="16"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-lg tracking-wider" dir="ltr">
                            @error('cardNumber') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">تاریخ انقضا (اختیاری)</label>
                                <input type="text" wire:model.live="expiryDate" placeholder="MM/YY" maxlength="5"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-center" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CVV2 (اختیاری)</label>
                                <input type="text" wire:model.live="cvv2" placeholder="123" maxlength="4"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-center" dir="ltr">
                            </div>
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

        {{-- Desktop Preview --}}
        <div class="lg:col-span-2">
            <div class="sticky top-8">
                @php
                    $isLight = $this->isLightColor();
                    $textMain = $isLight ? 'text-gray-800' : 'text-white/80';
                    $textSub = $isLight ? 'text-gray-500' : 'text-white/40';
                    $textMuted = $isLight ? 'text-gray-600' : 'text-white/60';
                    $chipBg = $isLight ? 'bg-gray-700/20 border-gray-700/20' : 'bg-white/20 border-white/20';
                @endphp
                @if($step <= 6)
                    <h3 class="text-sm font-medium text-gray-500 mb-3 text-center">پیش‌نمایش زنده</h3>
                    <div class="relative mx-auto" style="max-width: 340px;">
                        <div class="relative w-full rounded-2xl shadow-2xl overflow-hidden border {{ $isLight ? 'border-gray-300' : 'border-gray-200' }}"
                            style="padding-top: 63.3%; background-color: {{ $this->selectedColor?->color_code ?? '#1f2937' }}">
                            @if($this->selectedDesignImage?->image_path)
                                <img src="{{ asset('storage/' . $this->selectedDesignImage->image_path) }}" alt=""
                                    class="absolute inset-0 w-full h-full object-contain p-4 mix-blend-multiply">
                            @endif
                            <div class="absolute inset-0 p-5 flex flex-col justify-between">
                                <div class="flex justify-between items-start">
                                    <div class="w-12 h-9 rounded-md backdrop-blur-sm border {{ $chipBg }}"></div>
                                    <div class="{{ $textMuted }} text-xs font-medium">CARD</div>
                                </div>
                                <div class="{{ $textMain }}">
                                    <div class="text-lg tracking-widest font-mono mb-3">
                                        **** &nbsp; **** &nbsp; **** &nbsp; ****
                                    </div>
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <div class="text-[10px] {{ $textSub }} mb-0.5">CARD HOLDER</div>
                                            <div class="text-sm font-medium">{{ $holderName ?: 'نام دارنده کارت' }}</div>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-[10px] {{ $textSub }} mb-0.5">VALID THRU</div>
                                            <div class="text-sm font-medium">{{ $expiryDate ?: 'MM/YY' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($this->selectedCardType)
                            <div class="mt-4 text-center">
                                <span class="text-2xl font-bold text-gray-900">{{ number_format($this->selectedCardType->base_price) }}</span>
                                <span class="text-sm text-gray-500 mr-1">تومان</span>
                            </div>
                        @endif
                    </div>

                @elseif($step === 7)
                    <h3 class="text-sm font-medium text-gray-500 mb-3 text-center">پیش‌نمایش پشت کارت</h3>
                    <p class="text-xs text-gray-400 mb-4 text-center">فیلدها را بکشید و جای آن‌ها را تغییر دهید</p>

                    <div class="relative mx-auto" style="max-width: 340px;" wire:ignore
                        x-data="{
                            dragging: null,
                            cardEl: null,
                            offsetX: 0,
                            offsetY: 0,
                            positions: Object.fromEntries(Object.entries({{ json_encode($fieldPositions) }}).map(([k,v]) => [k, {...v, fontSize: Number(v.fontSize)}])),
                            startDrag(event, field) {
                                event.preventDefault();
                                this.dragging = field;
                                const fieldEl = event.target.closest('.draggable-field');
                                this.cardEl = fieldEl.closest('.card-back');
                                const fRect = fieldEl.getBoundingClientRect();
                                const clientX = event.clientX || (event.touches && event.touches[0] ? event.touches[0].clientX : 0);
                                const clientY = event.clientY || (event.touches && event.touches[0] ? event.touches[0].clientY : 0);
                                this.offsetX = clientX - fRect.left;
                                this.offsetY = clientY - fRect.top;
                                if (event.type === 'touchstart') {
                                    this.scrollY = window.scrollY;
                                    document.documentElement.style.overflow = 'hidden';
                                    document.body.style.overflow = 'hidden';
                                }
                            },
                            onDrag(event) {
                                if (!this.dragging || !this.cardEl) return;
                                event.preventDefault();
                                const rect = this.cardEl.getBoundingClientRect();
                                const clientX = event.clientX || (event.touches && event.touches[0] ? event.touches[0].clientX : 0);
                                const clientY = event.clientY || (event.touches && event.touches[0] ? event.touches[0].clientY : 0);
                                let newLeft = ((clientX - rect.left - this.offsetX) / rect.width) * 100;
                                let newTop = ((clientY - rect.top - this.offsetY) / rect.height) * 100;
                                newLeft = Math.max(0, Math.min(100 - parseFloat(this.positions[this.dragging].width), newLeft));
                                newTop = Math.max(0, Math.min(90, newTop));
                                this.positions[this.dragging].left = newLeft.toFixed(1);
                                this.positions[this.dragging].top = newTop.toFixed(1);
                            },
                            endDrag() {
                                if (this.dragging) {
                                    const field = this.dragging;
                                    const pos = this.positions[field];
                                    $wire.call('updatePosition', field, pos.top, pos.left);
                                    this.dragging = null;
                                    this.cardEl = null;
                                }
                                document.documentElement.style.overflow = '';
                                document.body.style.overflow = '';
                                window.scrollTo(0, this.scrollY || 0);
                            }
                        }"
                        @mousemove.window="onDrag($event)"
                        @touchmove.window="onDrag($event)"
                        @mouseup.window="endDrag()"
                        @touchend.window="endDrag()"
                    >
                        <div class="card-back relative w-full rounded-2xl shadow-2xl overflow-hidden border border-gray-200 bg-gray-100"
                            style="padding-top: 63.3%; touch-action: none;">
                            <div class="absolute inset-0 bg-gradient-to-b from-gray-200 to-gray-300">
                                <div class="absolute top-[8%] left-0 right-0 h-[12%] bg-gray-700"></div>

                                <div class="draggable-field absolute text-gray-700 font-mono tracking-wider cursor-move select-none bg-white/80 px-2 py-1 rounded shadow-sm"
                                    :style="`touch-action:none; top:${positions.card_number.top}%; left:${positions.card_number.left}%; width:${positions.card_number.width}%; font-size:${positions.card_number.fontSize}px;`"
                                    @mousedown="startDrag($event, 'card_number')" @touchstart.prevent="startDrag($event, 'card_number')" dir="ltr">
                                    {{ $this->getFormattedCardNumber() }}
                                </div>
                                <div class="draggable-field absolute text-gray-700 cursor-move select-none bg-white/80 px-2 py-1 rounded shadow-sm"
                                    :style="`touch-action:none; top:${positions.holder_name.top}%; left:${positions.holder_name.left}%; width:${positions.holder_name.width}%; font-size:${positions.holder_name.fontSize}px;`"
                                    @mousedown="startDrag($event, 'holder_name')" @touchstart.prevent="startDrag($event, 'holder_name')">
                                    {{ $holderName ?: 'نام دارنده کارت' }}
                                </div>
                                <div class="draggable-field absolute text-gray-700 font-mono cursor-move select-none bg-white/80 px-2 py-1 rounded shadow-sm"
                                    :style="`touch-action:none; top:${positions.expiry_date.top}%; left:${positions.expiry_date.left}%; width:${positions.expiry_date.width}%; font-size:${positions.expiry_date.fontSize}px;`"
                                    @mousedown="startDrag($event, 'expiry_date')" @touchstart.prevent="startDrag($event, 'expiry_date')" dir="ltr">
                                    {{ $expiryDate ?: 'MM/YY' }}
                                </div>
                                <div class="draggable-field absolute text-gray-700 font-mono cursor-move select-none bg-white/80 px-2 py-1 rounded shadow-sm"
                                    :style="`touch-action:none; top:${positions.cvv2.top}%; left:${positions.cvv2.left}%; width:${positions.cvv2.width}%; font-size:${positions.cvv2.fontSize}px;`"
                                    @mousedown="startDrag($event, 'cvv2')" @touchstart.prevent="startDrag($event, 'cvv2')" dir="ltr">
                                    {{ $cvv2 ?: '***' }}
                                </div>

                                <div class="absolute bottom-[8%] left-[5%] right-[5%] h-[6%] bg-gray-400/50 rounded flex items-center justify-center">
                                    <span class="text-[8px] text-gray-600 font-mono" dir="ltr">CVV2: {{ $cvv2 ?: '***' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Font Size Controls --}}
                        <div class="mt-4 space-y-2">
                            @foreach(['card_number' => 'شماره کارت', 'holder_name' => 'نام دارنده', 'expiry_date' => 'تاریخ', 'cvv2' => 'CVV2'] as $field => $label)
                                <div class="flex items-center gap-3 bg-white rounded-lg border border-gray-200 p-2">
                                    <span class="text-xs text-gray-500 w-20">{{ $label }}</span>
                                    <button @click="positions.{{ $field }}.fontSize = Math.max(8, positions.{{ $field }}.fontSize - 1); $wire.updateFontSize('{{ $field }}', String(positions.{{ $field }}.fontSize))"
                                        class="w-7 h-7 rounded bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-sm font-bold">-</button>
                                    <span class="text-xs text-gray-600 w-8 text-center" x-text="positions.{{ $field }}.fontSize + 'px'"></span>
                                    <button @click="positions.{{ $field }}.fontSize = Math.min(24, positions.{{ $field }}.fontSize + 1); $wire.updateFontSize('{{ $field }}', String(positions.{{ $field }}.fontSize))"
                                        class="w-7 h-7 rounded bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-sm font-bold">+</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MOBILE: Selection panel (below preview) --}}
    <div class="lg:hidden">
        @if($step === 1)
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">رنگ کارت بانکی را انتخاب کنید</h2>
                <p class="text-sm text-gray-500 mb-4">رنگ فلز کارت خود را انتخاب کنید</p>
                <div class="grid grid-cols-3 gap-2">
                    @forelse($this->availableColors as $color)
                        <button wire:click="selectColor({{ $color->id }})"
                            class="group p-3 bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 transition text-center">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full mx-auto mb-1.5 border-2 border-gray-200 shadow-inner"
                                style="background-color: {{ $color->color_code }}"></div>
                            <span class="text-xs sm:text-sm font-medium text-gray-700">{{ $color->name }}</span>
                        </button>
                    @empty
                        <p class="col-span-full text-center text-gray-400 py-8">رنگی موجود نیست</p>
                    @endforelse
                </div>
            </div>

        @elseif($step === 2)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">دسته‌بندی طرح را انتخاب کنید</h2>
                <div class="grid grid-cols-2 gap-3">
                    @forelse($this->cateDesigns as $category)
                        <button wire:click="selectCateDesign({{ $category->id }})"
                            class="p-3 sm:p-4 bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 transition text-right">
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-1">{{ $category->name }}</h3>
                            <p class="text-xs text-gray-400">{{ $category->groupDesigns->count() }} گروه طرح</p>
                        </button>
                    @empty
                        <p class="col-span-full text-center text-gray-400 py-8">دسته‌بندی‌ای موجود نیست</p>
                    @endforelse
                </div>
            </div>

        @elseif($step === 3)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">گروه طرح را انتخاب کنید</h2>
                <div class="grid grid-cols-2 gap-3">
                    @forelse($this->groupDesigns as $group)
                        <button wire:click="selectGroupDesign({{ $group->id }})"
                            class="p-3 sm:p-4 bg-white rounded-xl border-2 border-gray-200 hover:border-yellow-500 transition text-right">
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-1">{{ $group->name }}</h3>
                            <p class="text-xs text-gray-400">{{ $group->designs->count() }} طرح</p>
                        </button>
                    @empty
                        <p class="col-span-full text-center text-gray-400 py-8">گروه‌ای موجود نیست</p>
                    @endforelse
                </div>
            </div>

        @elseif($step === 4)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">طرح را انتخاب کنید</h2>
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
                            </div>
                        </button>
                    @empty
                        <p class="col-span-full text-center text-gray-400 py-8">طرحی موجود نیست</p>
                    @endforelse
                </div>
            </div>

        @elseif($step === 5)
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

        @elseif($step === 6)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">پیش‌نمایش نهایی</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">رنگ کارت:</span>
                            <span class="font-medium">{{ $this->selectedColor?->name }}</span>
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

        @elseif($step === 7)
            <div>
                <button wire:click="goBack" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
                    <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    بازگشت
                </button>
                <h2 class="text-xl font-bold text-gray-900 mb-2">اطلاعات پشت کارت</h2>
                <p class="text-sm text-gray-500 mb-4">فیلدها را پر کنید</p>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نام دارنده کارت <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="holderName" placeholder="نام کامل"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                        @error('holderName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شماره کارت <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="cardNumber" placeholder="0000 0000 0000 0000" maxlength="16"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-base tracking-wider" dir="ltr">
                        @error('cardNumber') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">تاریخ انقضا</label>
                            <input type="text" wire:model.live="expiryDate" placeholder="MM/YY" maxlength="5"
                                class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-center text-sm" dir="ltr">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">CVV2</label>
                            <input type="text" wire:model.live="cvv2" placeholder="123" maxlength="4"
                                class="w-full px-3 py-2.5 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition font-mono text-center text-sm" dir="ltr">
                        </div>
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
