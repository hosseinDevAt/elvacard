<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">مدیریت سفارشات کاربران</h2>
            <p class="text-xs text-slate-500 mt-1">مشاهده سفارشات، بررسی فیش‌های واریزی و تغییر وضعیت سفارش</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="$set('statusFilter', null)" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ !$statusFilter ? 'bg-[#010619] text-[#ffde5b] shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">همه</button>
            <button type="button" wire:click="$set('statusFilter', 'pending')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/20' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">در انتظار پرداخت</button>
            <button type="button" wire:click="$set('statusFilter', 'confirmed')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'confirmed' ? 'bg-sky-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">تایید شده</button>
            <button type="button" wire:click="$set('statusFilter', 'production')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'production' || $statusFilter === 'processing' ? 'bg-slate-800 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">در حال تولید</button>
            <button type="button" wire:click="$set('statusFilter', 'completed')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'completed' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">تکمیل شده</button>
            <button type="button" wire:click="$set('statusFilter', 'cancelled')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'cancelled' ? 'bg-rose-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">لغو شده</button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <x-icons.check-badge class="mt-0.5 shrink-0 text-green-600" />
            <div class="min-w-0">{{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($selectedOrder)
        <div class="mb-6 admin-card overflow-hidden border border-slate-200/80 shadow-md">
            {{-- Section A: Order Summary Header & Quick KPIs --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/70">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold text-slate-900">سفارش</h2>
                            <span class="font-mono text-xs font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200 select-all" dir="ltr">{{ $selectedOrder->reference }}</span>
                            @if ($selectedOrder->user)
                                <span class="text-[11px] bg-[#ffde5b]/25 text-[#664d00] font-bold px-2 py-0.5 rounded-full border border-[#ffde5b]/60">کاربر ثبت‌نام‌شده</span>
                            @else
                                <span class="text-[11px] bg-slate-100 text-slate-600 font-medium px-2 py-0.5 rounded-full border border-slate-200">سفارش مهمان</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">ثبت شده در: {{ jalali_date($selectedOrder->created_at, 'datetime') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="closeOrderDetail" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition shadow-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                        بازگشت به لیست
                    </button>
                </div>
            </div>

            {{-- Summary Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-6 py-4 text-sm bg-white border-b border-slate-100">
                <div class="p-3 rounded-xl bg-slate-50/60 border border-slate-100">
                    <div class="text-slate-400 text-xs font-medium mb-1">مشتری</div>
                    <div class="text-slate-900 font-bold text-xs sm:text-sm">{{ $selectedOrder->user->name ?? $selectedOrder->customer_name }}</div>
                    <div class="text-slate-600 text-xs font-mono mt-0.5" dir="ltr">{{ $selectedOrder->user->phone ?? $selectedOrder->customer_phone }}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50/60 border border-slate-100">
                    <div class="text-slate-400 text-xs font-medium mb-1">مبلغ کل سفارش</div>
                    <div class="text-slate-900 font-bold font-mono text-sm sm:text-base text-amber-600">{{ number_format($selectedOrder->total_price) }} <span class="text-xs text-slate-500 font-sans">تومان</span></div>
                    <div class="text-[11px] text-slate-500 mt-0.5">وضعیت پرداخت: <span class="font-bold text-slate-700">{{ $selectedOrder->payment_status->faLabel() }}</span></div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50/60 border border-slate-100">
                    <div class="text-slate-400 text-xs font-medium mb-1">وضعیت جاری سفارش</div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-extrabold {{ $selectedOrder->status === \App\Enums\OrderStatusEnum::COMPLETED ? 'bg-emerald-100 text-emerald-800' : ($selectedOrder->status === \App\Enums\OrderStatusEnum::CANCELLED ? 'bg-rose-100 text-rose-800' : ($selectedOrder->status === \App\Enums\OrderStatusEnum::PRODUCTION ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800')) }}">
                            {{ $selectedOrder->status->faLabel() }}
                        </span>
                    </div>
                    @php $modalTargets = $transitions[$selectedOrder->id] ?? []; @endphp
                    @if (!empty($modalTargets))
                        <div class="mt-2">
                            <select wire:change="updateStatus({{ $selectedOrder->id }}, $event.target.value)"
                                class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 transition focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]/40">
                                <option value="">تغییر وضعیت سفارش...</option>
                                @foreach ($modalTargets as $target)
                                    <option value="{{ $target['value'] }}">{{ $target['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="p-3 rounded-xl bg-slate-50/60 border border-slate-100">
                    <div class="text-slate-400 text-xs font-medium mb-1">اطلاعات ارسال و پستی</div>
                    <div class="text-xs text-slate-800 line-clamp-1" title="{{ $selectedOrder->shipping_address ?? 'ثبت نشده' }}">{{ $selectedOrder->shipping_address ?? 'ثبت نشده' }}</div>
                    <div class="text-[11px] text-slate-500 mt-1 font-mono" dir="ltr">کد پستی: {{ $selectedOrder->shipping_postal_code ?? '—' }}</div>
                </div>
            </div>

            {{-- Shipping & Notes Detail --}}
            <div class="px-6 py-3 bg-slate-50/40 text-xs border-b border-slate-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-slate-600">
                    <div><span class="text-slate-400">آدرس کامل:</span> <span class="text-slate-900 font-medium">{{ $selectedOrder->shipping_address ?? 'ثبت نشده' }}</span></div>
                    <div><span class="text-slate-400">پلاک:</span> <span class="text-slate-900 font-medium">{{ $selectedOrder->shipping_plaque ?? '—' }}</span></div>
                    <div><span class="text-slate-400">توضیح تحویل:</span> <span class="text-slate-900 font-medium">{{ $selectedOrder->shipping_description ?? '—' }}</span></div>
                    <div>
                        <span class="text-slate-400">یادداشت سفارش:</span> 
                        <span class="font-bold {{ filled($selectedOrder->notes) ? 'text-amber-700' : 'text-slate-400' }}">{{ $selectedOrder->notes ?: '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Section B: Order Items & Manufacturing Workspace --}}
            <div class="px-6 py-5 bg-slate-50/30 space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        اقلام سفارش و مشخصات تولید (Manufacturing Workspace)
                    </h3>
                    <span class="text-xs text-slate-500 font-medium">{{ $selectedOrder->items->count() }} ردیف سفارش</span>
                </div>

                @foreach ($selectedOrder->items as $item)
                    @php
                        $custom = is_array($item->customization_json) ? $item->customization_json : [];
                        $slots = \App\Services\Customization\CardPresenter::fixedSlots();
                        $itemWorkflow = $item->customization_workflow;
                        if ($itemWorkflow === null && !empty($custom)) {
                            $itemWorkflow = \App\Services\Customization\CustomizationWorkflowRegistry::classifyLegacyCustomization($custom);
                        }
                        if ($itemWorkflow === null && $item->product) {
                            if ($item->product->type === \App\Enums\ProductTypeEnum::BANK) {
                                $itemWorkflow = \App\Enums\CustomizationWorkflowEnum::BANK_CARD;
                            } elseif ($item->product->type === \App\Enums\ProductTypeEnum::FUEL) {
                                $itemWorkflow = \App\Enums\CustomizationWorkflowEnum::FUEL_CARD;
                            }
                        }
                        $isBankCard = $itemWorkflow === \App\Enums\CustomizationWorkflowEnum::BANK_CARD;
                        $isFuelCard = $itemWorkflow === \App\Enums\CustomizationWorkflowEnum::FUEL_CARD;
                        $isCustomCard = $isBankCard || $isFuelCard;
                    @endphp

                    @if ($isCustomCard)
                        {{-- Custom Card Item (Bank Card or Fuel Card) --}}
                        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5 space-y-5">
                            {{-- Line Item Header --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ $isBankCard ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-slate-900 text-sm sm:text-base">{{ $item->product_name_snapshot }}</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $isBankCard ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                                {{ $isBankCard ? 'کارت بانکی شخصی‌سازی شده' : 'کارت سوخت شخصی‌سازی شده' }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-slate-500">تعداد: <span class="font-bold text-slate-700">{{ $item->quantity }}</span> عدد</span>
                                    </div>
                                </div>
                                <div class="text-xs font-mono font-bold text-slate-700 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                                    قیمت واحد: {{ number_format($item->unit_price_snapshot) }} تومان | مجموع: <span class="text-amber-600 font-black">{{ number_format($item->final_price) }} تومان</span>
                                </div>
                            </div>

                            @if ($isBankCard)
                                @php
                                    $fullPan = $item->getDecryptedPan();
                                    $formattedPan = $item->getFormattedDecryptedPan() ?? ($fullPan ? \App\Services\Customization\CardPresenter::presentCardNumber($fullPan) : ($item->getMaskedPan() ?? 'ثبت نشده'));
                                    $decryptedCvv = $item->getDecryptedCvv();
                                    $displayCvv = $decryptedCvv ?: ($custom['cvv2'] ?? ($custom['cvv'] ?? null));
                                    $formattedExpiry = (!empty($custom['expiry_month']) && !empty($custom['expiry_year'])) 
                                        ? (str_pad((string)$custom['expiry_month'], 2, '0', STR_PAD_LEFT) . '/' . substr((string)$custom['expiry_year'], -2)) 
                                        : (!empty($custom['expiry']) ? $custom['expiry'] : null);
                                    $colorName = mb_strtolower($item->color_name_snapshot ?? '');
                                    $bgGradient = match (true) {
                                        str_contains($colorName, 'طلایی') => 'from-amber-400 via-yellow-500 to-amber-600 text-gray-950',
                                        str_contains($colorName, 'نقره') => 'from-slate-200 via-gray-300 to-slate-400 text-gray-900',
                                        str_contains($colorName, 'مسی') => 'from-amber-700 via-orange-800 to-amber-900 text-amber-100',
                                        str_contains($colorName, 'رزگلد') || str_contains($colorName, 'رز') => 'from-rose-300 via-pink-400 to-rose-500 text-gray-900',
                                        default => 'from-gray-800 via-gray-900 to-black text-amber-200/90',
                                    };
                                @endphp

                                {{-- Bank Card Specifications Grid --}}
                                <div class="space-y-3 bg-slate-50/70 p-4 rounded-xl border border-slate-200/70 text-xs">
                                    <div class="font-bold text-slate-800 mb-1 border-b border-slate-200 pb-2 flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            پارامترهای حکاکی کاربر (اطلاعات کامل کارت جهت تولید):
                                        </span>
                                        <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-mono font-bold">PRODUCTION SPEC</span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/60">
                                            <span class="text-slate-400 block text-[11px]">رنگ متریال کارت:</span>
                                            <span class="text-slate-900 font-bold text-xs">{{ $item->color_name_snapshot ?? 'ثبت نشده' }}</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/60">
                                            <span class="text-slate-400 block text-[11px]">طرح انتخابی:</span>
                                            <span class="text-slate-900 font-bold text-xs">{{ $item->design_name_snapshot ?? 'ثبت نشده' }}</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/60">
                                            <span class="text-slate-400 block text-[11px]">رنگ طرح انتخابی:</span>
                                            @php
                                                $designColorName = $item->getDesignColorName();
                                                $designColorHex = $item->getDesignColorHex();
                                            @endphp
                                            @if ($designColorName)
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    @if ($designColorHex)
                                                        <span class="h-3.5 w-3.5 rounded-full border border-slate-300 shadow-sm shrink-0" style="background-color: {{ $designColorHex }};" title="{{ $designColorHex }}"></span>
                                                    @endif
                                                    <span class="text-slate-900 font-bold text-xs">{{ $designColorName }}</span>
                                                </div>
                                            @else
                                                <span class="text-slate-500 font-medium text-xs">ثبت نشده</span>
                                            @endif
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/60">
                                            <span class="text-slate-400 block text-[11px]">نام دارنده کارت:</span>
                                            <span class="text-slate-900 font-bold text-xs select-all">{{ $custom['card_holder_name'] ?? 'ثبت نشده' }}</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/60">
                                            <span class="text-slate-400 block text-[11px]">متن دلخواه پشت کارت:</span>
                                            <span class="text-slate-900 font-bold text-xs select-all">{{ $custom['back_text'] ?? 'ثبت نشده' }}</span>
                                        </div>
                                    </div>

                                    {{-- Full Card Production Parameters (PAN, Expiry, CVV2) --}}
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 pt-1">
                                        <div class="md:col-span-6 bg-gradient-to-r from-amber-50 to-orange-50 p-3 rounded-xl border border-amber-200 flex items-center justify-between">
                                            <div>
                                                <span class="text-amber-800 font-bold block text-[11px] mb-0.5">شماره کامل کارت (PAN):</span>
                                                <span class="font-mono text-slate-950 font-black text-sm sm:text-base tracking-widest select-all" dir="ltr">{{ $formattedPan }}</span>
                                            </div>
                                            @if ($fullPan)
                                                <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText('{{ $fullPan }}'); copied = true; setTimeout(() => copied = false, 2000)" class="ms-2 px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-200/80 hover:bg-amber-300 text-amber-900 transition">
                                                    <span x-show="!copied">کپی</span>
                                                    <span x-show="copied" class="text-green-700">کپی شد!</span>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="md:col-span-3 bg-white p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                                            <div>
                                                <span class="text-slate-400 block text-[11px]">تاریخ انقضا:</span>
                                                <span class="font-mono text-slate-900 font-extrabold text-sm select-all" dir="ltr">
                                                    {{ $formattedExpiry ?? 'ثبت نشده' }}
                                                </span>
                                            </div>
                                            @if ($formattedExpiry)
                                                <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText('{{ $formattedExpiry }}'); copied = true; setTimeout(() => copied = false, 2000)" class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                                    <span x-show="!copied">کپی</span>
                                                    <span x-show="copied" class="text-green-700">✓</span>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="md:col-span-3 bg-white p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                                            <div>
                                                <span class="text-slate-400 block text-[11px]">کد امنیتی CVV2:</span>
                                                <span class="font-mono text-slate-900 font-extrabold text-sm select-all" dir="ltr">
                                                    {{ $displayCvv ?? 'ثبت نشده' }}
                                                </span>
                                            </div>
                                            @if ($displayCvv)
                                                <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText('{{ $displayCvv }}'); copied = true; setTimeout(() => copied = false, 2000)" class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                                    <span x-show="!copied">کپی</span>
                                                    <span x-show="copied" class="text-green-700">✓</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Bank Card Balanced Previews (Front & Back CR-80) --}}
                                <div class="mt-4 pt-3 border-t border-slate-100">
                                    <div class="text-xs font-bold text-slate-700 mb-3 flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            پیش‌نمایش بصری دوطرفه کارت بانکی (2D Snapshot Preview جهت تولید):
                                        </span>
                                        <span class="text-[10px] bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-full font-mono font-bold">CR-80 / 85.60 × 53.98 mm</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {{-- Front Preview --}}
                                        <div class="flex flex-col space-y-1.5">
                                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-1">
                                                <span>نمای رو (FRONT)</span>
                                                <span class="text-slate-400 font-mono text-[10px]">طرح: {{ $item->design_name_snapshot ?? '—' }} | متریال: {{ $item->color_name_snapshot ?? '—' }}@if ($designColorName) | رنگ طرح: {{ $designColorName }}@endif</span>
                                            </div>
                                            <div class="w-full aspect-[1.586/1] rounded-2xl p-5 shadow-lg relative overflow-hidden select-none border border-black/15 bg-gradient-to-br {{ $bgGradient }}" dir="ltr">
                                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-white/0 via-white/20 to-white/0 opacity-60"></div>
                                                {{-- Chip Element --}}
                                                <div class="absolute top-1/2 -translate-y-1/2 left-5 sm:left-7 h-9 w-12 rounded-lg bg-gradient-to-br from-amber-300 via-yellow-400 to-amber-600 border border-yellow-100/80 shadow-md flex items-center justify-center pointer-events-none opacity-95">
                                                    <div class="w-full h-[1px] bg-amber-900/40"></div>
                                                    <div class="absolute inset-y-0 w-[1px] bg-amber-900/40"></div>
                                                    <div class="h-4 w-6 rounded-[2px] border border-amber-900/30 bg-amber-400/20"></div>
                                                </div>
                                                {{-- Contactless waves indicator --}}
                                                <div class="absolute top-4 left-5 opacity-70 pointer-events-none">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.393 9.393c5.857-5.857 15.356-5.857 21.213 0" />
                                                    </svg>
                                                </div>
                                                @if ($item->design_image_path_snapshot)
                                                    <div class="absolute inset-0 flex items-center justify-center p-3 opacity-60 pointer-events-none">
                                                        <img src="{{ asset('storage/' . $item->design_image_path_snapshot) }}" alt="{{ $item->design_name_snapshot }}" class="max-h-full max-w-full object-contain">
                                                    </div>
                                                @endif
                                                <div class="absolute bottom-3 right-4 text-end opacity-80">
                                                    <span class="text-[11px] font-bold font-sans tracking-wide uppercase">{{ $item->design_name_snapshot ?? 'ELVA CARD' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Back Preview --}}
                                        <div class="flex flex-col space-y-1.5">
                                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-1">
                                                <span>نمای پشت (BACK)</span>
                                                <span class="text-amber-700 font-mono text-[10px]">چاپ و حکاکی لیزری</span>
                                            </div>
                                            <div class="w-full aspect-[1.586/1] rounded-2xl p-4 shadow-lg relative overflow-hidden select-none border border-black/15 bg-gradient-to-br {{ $bgGradient }}" dir="ltr">
                                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-white/0 via-white/10 to-white/0 opacity-60"></div>
                                                {{-- Magnetic Stripe --}}
                                                <div class="absolute top-2.5 inset-x-0 h-7 sm:h-8 bg-neutral-950 shadow-inner pointer-events-none"></div>

                                                {{-- Slot 1: Card Number --}}
                                                @if (!empty($fullPan) || !empty($formattedPan))
                                                    <div class="absolute rounded px-1" style="left: {{ $slots['card_number']['x'] * 100 }}%; top: {{ $slots['card_number']['y'] * 100 }}%;">
                                                        <div class="font-mono text-xs sm:text-sm font-bold tracking-widest opacity-95 text-shadow select-all" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">
                                                            {{ $formattedPan }}
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Slot 2: Card Holder Name / Signature Box --}}
                                                @if (!empty($custom['card_holder_name']))
                                                    <div class="absolute rounded px-1" style="left: {{ $slots['card_holder_name']['x'] * 100 }}%; top: {{ $slots['card_holder_name']['y'] * 100 }}%;">
                                                        <div class="h-6 bg-white/95 rounded px-2 flex items-center text-gray-900 font-serif italic text-[11px] font-bold tracking-wider shadow-inner select-all">
                                                            {{ $custom['card_holder_name'] }}
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Slot 3: Back Text --}}
                                                @if (!empty($custom['back_text']))
                                                    <div class="absolute rounded px-1 max-w-[180px]" style="left: {{ $slots['back_text']['x'] * 100 }}%; top: {{ $slots['back_text']['y'] * 100 }}%;">
                                                        <div class="text-[10px] font-medium italic opacity-90 truncate select-all">
                                                            {{ $custom['back_text'] }}
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Slot 4: CVV2 --}}
                                                @if (!empty($displayCvv))
                                                    <div class="absolute rounded px-1" style="left: {{ $slots['cvv2']['x'] * 100 }}%; top: {{ $slots['cvv2']['y'] * 100 }}%;">
                                                        <div class="text-[8px] font-bold tracking-widest opacity-75">CVV2</div>
                                                        <div class="font-mono font-bold text-[11px] tracking-widest select-all" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">
                                                            {{ $displayCvv }}
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Slot 5: Expiry Date --}}
                                                @if (!empty($formattedExpiry))
                                                    <div class="absolute rounded px-1" style="left: {{ $slots['expiry']['x'] * 100 }}%; top: {{ $slots['expiry']['y'] * 100 }}%;">
                                                        <div class="text-[8px] font-bold tracking-widest opacity-75">EXPIRES</div>
                                                        <div class="font-mono font-bold text-[11px] tracking-wider select-all" dir="ltr" style="direction: ltr; unicode-bidi: isolate;">
                                                            {{ $formattedExpiry }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($isFuelCard)
                                @php
                                    $fuelFields = \App\Services\FuelCard\FuelCardCustomization::fulfillmentFields($custom);
                                @endphp
                                {{-- Fuel Card Specifications Grid --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                    <div class="space-y-2 bg-slate-50/70 p-4 rounded-xl border border-slate-200/70">
                                        <div class="font-bold text-slate-800 mb-1 border-b border-slate-200 pb-1 flex items-center justify-between">
                                            <span>مشخصات کارت سوخت:</span>
                                            <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-mono font-bold">BASE SPEC</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 pt-1">
                                            <div class="bg-white p-2 rounded-lg border border-slate-200/60">
                                                <span class="text-slate-400 block text-[11px]">روش شخصی‌سازی:</span>
                                                <span class="text-slate-900 font-bold">{{ $itemWorkflow->faLabel() }}</span>
                                            </div>
                                            <div class="bg-white p-2 rounded-lg border border-slate-200/60">
                                                <span class="text-slate-400 block text-[11px]">رنگ متریال کارت:</span>
                                                <span class="text-slate-900 font-bold">{{ $item->color_name_snapshot ?? 'ثبت نشده' }}</span>
                                            </div>
                                            <div class="bg-white p-2 rounded-lg border border-slate-200/60">
                                                <span class="text-slate-400 block text-[11px]">طرح انتخابی:</span>
                                                <span class="text-slate-900 font-bold">{{ $item->design_name_snapshot ?? 'ثبت نشده' }}</span>
                                            </div>
                                            <div class="bg-white p-2 rounded-lg border border-slate-200/60">
                                                <span class="text-slate-400 block text-[11px]">رنگ طرح انتخابی:</span>
                                                @php
                                                    $fuelDesignColorName = $item->getDesignColorName();
                                                    $fuelDesignColorHex = $item->getDesignColorHex();
                                                @endphp
                                                @if ($fuelDesignColorName)
                                                    <div class="flex items-center gap-1.5 mt-0.5">
                                                        @if ($fuelDesignColorHex)
                                                            <span class="h-3.5 w-3.5 rounded-full border border-slate-300 shadow-sm shrink-0" style="background-color: {{ $fuelDesignColorHex }};" title="{{ $fuelDesignColorHex }}"></span>
                                                        @endif
                                                        <span class="text-slate-900 font-bold text-xs">{{ $fuelDesignColorName }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-slate-500 font-medium text-xs">ثبت نشده</span>
                                                @endif
                                            </div>
                                            <div class="bg-white p-2 rounded-lg border border-slate-200/60 col-span-2">
                                                <span class="text-slate-400 block text-[11px]">تصویر طرح:</span>
                                                <span class="text-slate-900 font-bold font-mono text-[11px] truncate block" dir="ltr">{{ $item->design_image_path_snapshot ?? 'ثبت نشده' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2 bg-emerald-50/60 p-4 rounded-xl border border-emerald-100">
                                        <div class="font-bold text-emerald-800 mb-1 border-b border-emerald-200 pb-1 flex items-center justify-between">
                                            <span>اطلاعات مشتری برای تولید کارت سوخت:</span>
                                            <span class="text-[10px] bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded font-mono font-bold">VEHICLE DATA</span>
                                        </div>
                                        <div class="space-y-1.5">
                                            @foreach ($fuelFields as $fuelKey => $fuelField)
                                                <div class="flex items-center justify-between bg-white/80 px-2.5 py-1 rounded border border-emerald-100">
                                                    <span class="text-slate-500 text-[11px]">{{ $fuelField['label'] }}:</span>
                                                    <span class="text-slate-900 font-bold font-mono text-xs select-all" @if (in_array($fuelKey, ['vin', 'plate_number', 'system_identifier'], true)) dir="ltr" @endif>
                                                        {{ $fuelField['value'] !== '' ? $fuelField['value'] : 'ثبت نشده' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Fuel Card Balanced Previews (Front & Back CR-80) --}}
                                <div class="mt-4 pt-3 border-t border-slate-100">
                                    <div class="text-xs font-bold text-slate-700 mb-3 flex items-center justify-between">
                                        <span>پیش‌نمایش بصری دوطرفه کارت سوخت (CR-80 Production Preview):</span>
                                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full font-mono font-bold">CR-80 SPEC</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {{-- Front Preview --}}
                                        <div class="flex flex-col space-y-1.5">
                                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-1">
                                                <span>نمای رو (FRONT)</span>
                                                <span class="text-slate-400 font-mono text-[10px]">کارت سوخت هوشمند | متریال: {{ $item->color_name_snapshot ?? '—' }}@if ($fuelDesignColorName) | رنگ طرح: {{ $fuelDesignColorName }}@endif</span>
                                            </div>
                                            <div class="w-full aspect-[1.586/1] rounded-2xl p-5 shadow-lg relative overflow-hidden select-none border border-black/15 bg-gradient-to-br from-emerald-900 via-slate-900 to-black text-amber-100" dir="ltr">
                                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-white/0 via-white/10 to-white/0 opacity-60"></div>
                                                <div class="absolute top-1/2 -translate-y-1/2 left-5 sm:left-7 h-9 w-12 rounded-lg bg-gradient-to-br from-amber-300 via-yellow-400 to-amber-600 border border-yellow-100/80 shadow-md flex items-center justify-center pointer-events-none opacity-95">
                                                    <div class="w-full h-[1px] bg-amber-900/40"></div>
                                                    <div class="absolute inset-y-0 w-[1px] bg-amber-900/40"></div>
                                                    <div class="h-4 w-6 rounded-[2px] border border-amber-900/30 bg-amber-400/20"></div>
                                                </div>
                                                @if ($item->design_image_path_snapshot)
                                                    <div class="absolute inset-0 flex items-center justify-center p-3 opacity-60 pointer-events-none">
                                                        <img src="{{ asset('storage/' . $item->design_image_path_snapshot) }}" alt="Fuel Design" class="max-h-full max-w-full object-contain">
                                                    </div>
                                                @endif
                                                <div class="absolute bottom-3 right-4 text-end opacity-85">
                                                    <span class="text-[11px] font-bold tracking-wider">SMART FUEL CARD</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Back Preview --}}
                                        <div class="flex flex-col space-y-1.5">
                                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-1">
                                                <span>نمای پشت (BACK)</span>
                                                <span class="text-emerald-700 font-mono text-[10px]">مشخصات فنی و هویتی</span>
                                            </div>
                                            <div class="w-full aspect-[1.586/1] rounded-2xl p-4 shadow-lg relative overflow-hidden select-none border border-black/15 bg-gradient-to-br from-slate-900 via-neutral-900 to-black text-white flex flex-col justify-between">
                                                <div class="w-full h-6 bg-black shadow-inner rounded-sm mb-1"></div>
                                                <div class="w-full my-auto overflow-hidden">
                                                    <table class="w-full text-start border-collapse text-[10px] text-slate-100 border border-white/20 rounded overflow-hidden">
                                                        <tbody>
                                                            <tr class="border-b border-white/20">
                                                                <th scope="row" class="py-1 px-2 text-start font-bold text-slate-300 w-2/5 bg-white/10">نام مالک</th>
                                                                <td class="py-1 px-2 font-bold text-white">{{ $custom['owner_name'] ?? '---' }}</td>
                                                            </tr>
                                                            <tr class="border-b border-white/20">
                                                                <th scope="row" class="py-1 px-2 text-start font-bold text-slate-300 bg-white/10">اطلاعات خودرو</th>
                                                                <td class="py-1 px-2 text-white">{{ $custom['car_info'] ?? '---' }}</td>
                                                            </tr>
                                                            <tr class="border-b border-white/20">
                                                                <th scope="row" class="py-1 px-2 text-start font-bold text-slate-300 bg-white/10">شماره شاسی (VIN)</th>
                                                                <td class="py-1 px-2 font-mono font-bold text-white select-all" dir="ltr">{{ $custom['vin'] ?? '---' }}</td>
                                                            </tr>
                                                            <tr class="border-b border-white/20">
                                                                <th scope="row" class="py-1 px-2 text-start font-bold text-slate-300 bg-white/10">شماره پلاک</th>
                                                                <td class="py-1 px-2 text-white">{{ $custom['plate_number'] ?? '---' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row" class="py-1 px-2 text-start font-bold text-slate-300 bg-white/10">شناسه سیستم / سریال</th>
                                                                <td class="py-1 px-2 font-mono text-white select-all" dir="ltr">{{ $custom['system_identifier'] ?? '---' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- Ordinary Store Product Line Item (No card previews, no card fabrication fields) --}}
                        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-20 w-20 shrink-0 rounded-xl border border-slate-200 bg-slate-50 p-1 flex items-center justify-center overflow-hidden">
                                        @if ($item->design_image_path_snapshot)
                                            <img src="{{ asset('storage/' . $item->design_image_path_snapshot) }}" alt="{{ $item->product_name_snapshot }}" class="h-full w-full object-contain">
                                        @elseif ($item->product?->main_image)
                                            <img src="{{ asset('storage/' . $item->product->main_image) }}" alt="{{ $item->product_name_snapshot }}" class="h-full w-full object-contain">
                                        @else
                                            <svg class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-extrabold text-slate-900 text-sm sm:text-base">{{ $item->product_name_snapshot }}</h4>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                                محصول فروشگاهی
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1 space-x-3 space-x-reverse">
                                            <span>تعداد: <span class="font-bold text-slate-800">{{ $item->quantity }}</span> عدد</span>
                                            @if ($item->color_name_snapshot)
                                                <span>رنگ / ویژگی: <span class="font-bold text-slate-800">{{ $item->color_name_snapshot }}</span></span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs font-mono font-bold text-slate-700 bg-slate-50 px-3 py-2 rounded-xl border border-slate-100 self-stretch sm:self-auto flex sm:flex-col items-center sm:items-end justify-between sm:justify-center">
                                    <span class="text-slate-400 font-sans text-[11px]">قیمت واحد: {{ number_format($item->unit_price_snapshot) }} تومان</span>
                                    <span class="text-amber-600 font-black text-sm">مجموع: {{ number_format($item->final_price) }} تومان</span>
                                </div>
                            </div>

                            @if (!empty($custom))
                                {{-- Non-card genuine custom product properties --}}
                                <div class="mt-3 pt-2 text-xs">
                                    <span class="text-slate-400 block mb-1">ویژگی‌های انتخابی:</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($custom as $optKey => $optVal)
                                            @if (!in_array($optKey, ['card_number', 'pan_encrypted', 'pan_last4', 'card_number_masked', 'pan_hash', 'cvv2', 'cvv_encrypted', 'cvv2_encrypted', 'expiry_month', 'expiry_year', 'security_cvv_enabled', 'security_expiry_enabled'], true) && is_scalar($optVal))
                                                <span class="bg-slate-50 text-slate-700 px-2 py-1 rounded border border-slate-200">
                                                    {{ $optKey }}: <span class="font-bold">{{ (string) $optVal }}</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>

            @php
                $allPayments = $selectedOrder->payments->sortByDesc('created_at')->values();
                $reasonLabels = [
                    'provider_exception' => 'خطای فراهم‌کننده',
                    'provider_verification_failed' => 'تأیید ناموفق درگاه',
                    'amount_mismatch' => 'مغایرت مبلغ',
                    'order_not_payable' => 'سفارش قابل پرداخت نبود',
                    'admin_rejected' => 'رد توسط ادمین',
                ];
            @endphp

            @if ($allPayments->isNotEmpty())
                @foreach ($allPayments as $index => $payment)
                    @php
                        $reason = $payment->metadata['reason'] ?? null;
                        $detail = $payment->metadata['detail'] ?? null;
                    @endphp
                    <div class="px-6 py-4 border-t border-gray-100">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <h3 class="font-bold text-gray-900">پرداخت #{{ $allPayments->count() - $index }}</h3>
                            <span class="admin-badge {{ $payment->status === \App\Enums\PaymentStatus::SUCCESS ? 'bg-green-100 text-green-800' : ($payment->status === \App\Enums\PaymentStatus::PENDING_REVIEW ? 'bg-amber-100 text-amber-700' : ($payment->status === \App\Enums\PaymentStatus::PENDING ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-700')) }}">
                                {{ $payment->status->faLabel() }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <div class="text-gray-400 text-xs">روش پرداخت</div>
                                <div class="text-gray-900">{{ $payment->method->faLabel() }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">مبلغ / پرداخت‌شده</div>
                                <div class="text-gray-900 font-mono">{{ number_format($payment->amount) }} / {{ $payment->paid_amount !== null ? number_format($payment->paid_amount) : '—' }} تومان</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">درگاه</div>
                                <div class="text-gray-900" dir="ltr">{{ $payment->gateway ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">شناسه تراکنش</div>
                                <div class="text-gray-900 break-all font-mono" dir="ltr">{{ $payment->transaction_id ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">کد رهگیری</div>
                                <div class="text-gray-900 break-all font-mono" dir="ltr">{{ $payment->tracking_code ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">تاریخ</div>
                                <div class="text-gray-900">ایجاد: {{ jalali_date($payment->created_at, 'datetime') }}</div>
                                @if ($payment->paid_at)
                                    <div class="text-green-700 text-xs">پرداخت: {{ jalali_date($payment->paid_at, 'datetime') }}</div>
                                @endif
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">توضیح مشتری / دلیل</div>
                                <div class="text-gray-900">
                                    {{ $payment->metadata['note'] ?? '—' }}
                                    @if ($reason)
                                        <span class="inline-block ms-2 text-xs px-2 py-0.5 rounded-lg bg-red-50 text-red-700">{{ $reasonLabels[$reason] ?? $reason }}</span>
                                        @if ($detail)
                                            <div class="text-xs text-gray-500 mt-1" title="{{ $detail }}">{{ $detail }}</div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">بررسی</div>
                                <div class="text-gray-900 text-xs">
                                    @if (isset($payment->metadata['reviewed_by'], $payment->metadata['reviewed_at']))
                                        توسط #{{ $payment->metadata['reviewed_by'] }} در {{ jalali_date($payment->metadata['reviewed_at'], 'datetime') }}
                                    @else
                                        — 
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($payment->receipt_path && $payment->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                            <div class="mt-4">
                                <div class="text-gray-400 text-xs mb-2">رسید</div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.payments.receipt', ['order' => $selectedOrder, 'payment' => $payment]) }}" target="_blank" class="px-3 py-1.5 rounded-lg text-xs bg-yellow-500 hover:bg-yellow-600 text-white">مشاهده رسید</a>
                                    <a href="{{ route('admin.payments.receipt', ['order' => $selectedOrder, 'payment' => $payment, 'download' => 1]) }}" class="px-3 py-1.5 rounded-lg text-xs bg-gray-800 hover:bg-gray-900 text-white">دانلود رسید</a>
                                </div>
                            </div>
                        @endif

                        @if ($payment->status === \App\Enums\PaymentStatus::PENDING_REVIEW && $payment->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                            <div class="mt-4 flex items-center gap-2">
                                <button type="button" wire:click="approvePayment({{ $payment->id }})" wire:confirm="آیا از تأیید این پرداخت مطمئن هستید؟" class="admin-btn admin-btn-success">تایید پرداخت</button>
                                <button type="button" wire:click="rejectPayment({{ $payment->id }})" wire:confirm="آیا از رد این پرداخت مطمئن هستید؟" class="admin-btn admin-btn-danger">رد پرداخت</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="px-6 py-4 border-t border-gray-100">
                    <div class="text-gray-400 text-sm">پرداختی برای این سفارش ثبت نشده است.</div>
                </div>
            @endif
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">سفارشات اخیر سیستم</h3>
            <span class="text-xs text-slate-400">نمایش وضعیت سفارشات و تغییرات</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-24">کد سفارش</th>
                        <th class="admin-th">مشتری</th>
                        <th class="admin-th">مبلغ سفارش</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th">تاریخ ثبت</th>
                        <th class="admin-th text-center">جزئیات</th>
                        <th class="admin-th">تغییر وضعیت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        @php
                            $statusColors = [
                                'pending' => 'admin-badge-warning',
                                'confirmed' => 'admin-badge-info',
                                'production' => 'admin-badge-info',
                                'processing' => 'admin-badge-info',
                                'completed' => 'admin-badge-success',
                                'cancelled' => 'admin-badge-danger',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td font-mono text-xs text-slate-500" dir="ltr">{{ $order->reference ?? '#'.$order->id }}</td>
                            <td class="admin-td">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                        {{ mb_substr($order->user?->name ?? $order->customer_name ?? 'م', 0, 1) }}
                                    </span>
                                    <div>
                                        <span class="font-bold text-slate-900 block text-xs">{{ $order->user?->name ?? ($order->customer_name ?? 'کاربر مهمان') }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono" dir="ltr">{{ $order->user?->phone ?? ($order->customer_phone ?? '-') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="admin-td font-bold text-slate-900 text-xs">{{ number_format($order->total_price) }} تومان</td>
                            <td class="admin-td">
                                <span class="{{ $statusColors[$order->status->value] ?? 'admin-badge-neutral' }} admin-badge">
                                    {{ $order->status->faLabel() }}
                                </span>
                            </td>
                            <td class="admin-td text-slate-400 text-xs">{{ jalali_relative($order->created_at) }}</td>
                            <td class="admin-td text-center">
                                <button type="button" wire:click="viewOrder({{ $order->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">
                                    جزئیات
                                </button>
                            </td>
                            <td class="admin-td">
                                @php $targets = $transitions[$order->id] ?? []; @endphp
                                @if (empty($targets))
                                    <span class="text-slate-400 text-xs">—</span>
                                @else
                                    <select wire:change="updateStatus({{ $order->id }}, $event.target.value)"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 transition focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]/40">
                                        <option value="">تغییر وضعیت...</option>
                                        @foreach ($targets as $target)
                                            <option value="{{ $target['value'] }}">{{ $target['label'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="admin-empty">سفارشی وجود ندارد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $orders->links() }}</div>
    </div>
</div>