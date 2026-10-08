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
        <div class="lg:col-span-5 min-w-0 p-6 sm:p-8 bg-[#070e24]/60 border-b lg:border-b-0 lg:border-e border-[#152244] flex flex-col justify-between space-y-6 overflow-y-auto pb-10">
            @if ($step === 1)
                {{-- STEP 1: Front of Card Customization --}}
                <div class="space-y-6">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1">۱. انتخاب طرح لیزر روی کارت</h2>
                        <p class="text-xs text-slate-400">طرح و دسته مورد نظر برای حکاکی روی کارت را انتخاب کنید.</p>
                    </div>

                    {{-- Design Source Segmented Control --}}
                    <div class="grid grid-cols-2 gap-1.5 p-1.5 rounded-2xl bg-[#010619] border border-[#152244]" role="tablist" aria-label="منبع انتخاب طرح کارت">
                        <button
                            type="button"
                            id="tab-catalog"
                            role="tab"
                            aria-selected="{{ $designSource === 'catalog' ? 'true' : 'false' }}"
                            aria-controls="panel-catalog"
                            wire:click="setDesignSource('catalog')"
                            class="min-h-[44px] h-11 rounded-xl px-3 text-xs font-bold transition-all duration-150 motion-reduce:transition-none cursor-pointer flex items-center justify-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ffde5b] {{ $designSource === 'catalog' ? 'bg-[#ffde5b] text-[#010619] font-black shadow-md shadow-[#ffde5b]/20' : 'text-slate-400 hover:text-slate-200 hover:bg-white/5' }}"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <span>طرح‌های آماده</span>
                        </button>
                        <button
                            type="button"
                            id="tab-custom"
                            role="tab"
                            aria-selected="{{ $designSource === 'custom' ? 'true' : 'false' }}"
                            aria-controls="panel-custom"
                            wire:click="setDesignSource('custom')"
                            class="min-h-[44px] h-11 rounded-xl px-3 text-xs font-bold transition-all duration-150 motion-reduce:transition-none cursor-pointer flex items-center justify-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ffde5b] {{ $designSource === 'custom' ? 'bg-[#ffde5b] text-[#010619] font-black shadow-md shadow-[#ffde5b]/20' : 'text-slate-400 hover:text-slate-200 hover:bg-white/5' }}"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>طرح اختصاصی شما</span>
                        </button>
                    </div>

                    @if ($designSource === 'catalog')
                        <div id="panel-catalog" role="tabpanel" aria-labelledby="tab-catalog" class="space-y-6">
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
                        {{-- Custom Design Upload Panel --}}
                        <div id="panel-custom" role="tabpanel" aria-labelledby="tab-custom" class="space-y-4">
                            @if (! $customDesignToken)
                                {{-- Custom Empty State: Luxury Dropzone --}}
                                <div class="relative rounded-2xl border-2 border-dashed border-[#152244] hover:border-[#ffde5b]/60 bg-[#010619] p-4 sm:p-6 text-center transition duration-200 group">
                                    <input
                                        type="file"
                                        id="custom-design-file-input"
                                        wire:model="customImage"
                                        accept="image/png,image/jpeg,image/webp"
                                        aria-describedby="custom-design-help-text"
                                        class="sr-only"
                                    >
                                    <label
                                        for="custom-design-file-input"
                                        tabindex="0"
                                        onkeydown="if(event.key === ' ' || event.key === 'Enter'){ event.preventDefault(); document.getElementById('custom-design-file-input').click(); }"
                                        class="flex flex-col items-center justify-center cursor-pointer space-y-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ffde5b] rounded-xl p-2 select-none"
                                    >
                                        <div class="h-12 w-12 rounded-full bg-[#ffde5b]/10 text-[#ffde5b] flex items-center justify-center group-hover:scale-105 transition duration-150 motion-reduce:transition-none shadow-inner">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="text-sm font-bold text-white group-hover:text-[#ffde5b] transition">تصویر طرح خود را بارگذاری کنید</div>
                                            <div class="text-xs text-slate-400">کلیک کنید یا فایل را اینجا بکشید</div>
                                            <div class="text-[11px] text-slate-500 font-mono" dir="ltr">PNG, JPG, WEBP · Max 5MB</div>
                                        </div>
                                    </label>

                                    <div id="custom-design-help-text" class="mt-3 rounded-xl bg-amber-400/5 border border-amber-400/15 p-2.5 text-[11px] text-amber-200/90 leading-relaxed text-start flex items-start gap-2">
                                        <svg class="w-4 h-4 text-[#ffde5b] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>برای ظرافت بیشتر حکاکی، تصویر تک‌رنگ یا PNG با پس‌زمینه شفاف پیشنهاد می‌شود.</span>
                                    </div>

                                    {{-- Upload Loading State --}}
                                    <div
                                        wire:loading
                                        wire:target="customImage"
                                        class="absolute inset-0 z-20 rounded-2xl bg-[#010619]/90 backdrop-blur-sm flex flex-col items-center justify-center space-y-3 p-4"
                                    >
                                        <svg class="animate-spin h-8 w-8 text-[#ffde5b]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <div class="text-center">
                                            <div class="text-xs font-bold text-white">در حال آماده‌سازی تصویر...</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">بررسی اصالت و اعتبارسنجی فایل</div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Custom Success State: File Card --}}
                                <div class="rounded-2xl bg-[#070e24] border border-[#152244] p-4 flex flex-col gap-3.5 shadow-lg">
                                    <div class="flex items-center gap-3">
                                        {{-- Thumbnail --}}
                                        <div class="h-14 w-14 rounded-xl bg-[#010619] border border-[#152244] p-1 flex items-center justify-center shrink-0 overflow-hidden shadow-inner">
                                            @if ($customDesignPreviewUrl)
                                                <img src="{{ $customDesignPreviewUrl }}" alt="پیش‌نمایش طرح" class="max-h-full max-w-full object-contain">
                                            @else
                                                <svg class="w-6 h-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            @endif
                                        </div>
                                        {{-- Info --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-bold text-white truncate" title="{{ $customDesignOriginalName }}">
                                                {{ $customDesignOriginalName ?? 'طرح اختصاصی شما' }}
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="inline-flex items-center gap-1 text-[11px] text-emerald-400 font-medium">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    تصویر تایید شده
                                                </span>
                                                @if ($this->formattedCustomDesignFileSize)
                                                    <span class="text-slate-500 text-[10px]">·</span>
                                                    <span class="text-[10px] text-slate-400 font-mono">{{ $this->formattedCustomDesignFileSize }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="flex items-center gap-2 pt-1 border-t border-[#152244]/80">
                                        {{-- Change Image Action --}}
                                        <label
                                            for="custom-design-file-input-change"
                                            tabindex="0"
                                            onkeydown="if(event.key === ' ' || event.key === 'Enter'){ event.preventDefault(); document.getElementById('custom-design-file-input-change').click(); }"
                                            class="min-h-[44px] flex-1 flex items-center justify-center gap-2 rounded-xl border border-[#152244] bg-[#010619] px-3 py-2 text-xs font-bold text-slate-300 hover:text-white hover:border-slate-600 transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ffde5b]"
                                        >
                                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span>تغییر تصویر</span>
                                            <input
                                                type="file"
                                                id="custom-design-file-input-change"
                                                wire:model="customImage"
                                                accept="image/png,image/jpeg,image/webp"
                                                class="sr-only"
                                            >
                                        </label>

                                        {{-- Remove Image Action --}}
                                        <button
                                            type="button"
                                            wire:click="removeCustomImage"
                                            class="min-h-[44px] flex items-center justify-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-bold text-rose-400 hover:bg-rose-500/20 transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>حذف تصویر</span>
                                        </button>
                                    </div>

                                    {{-- Loading State during Change Image --}}
                                    <div wire:loading wire:target="customImage" class="pt-2 text-xs text-[#ffde5b] flex items-center justify-center gap-2">
                                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>در حال آماده‌سازی تصویر جدید...</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Validation Error Feedback --}}
                            @if ($customDesignErrorMessage)
                                <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-start gap-2 leading-relaxed">
                                    <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>{{ $customDesignErrorMessage }}</span>
                                </div>
                            @endif
                            @error('customImage')
                                <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-start gap-2 leading-relaxed">
                                    <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror

                            {{-- Layout Mode Selector --}}
                            @if ($customDesignToken)
                                <div class="pt-3 border-t border-[#152244] space-y-2.5">
                                    <label class="block text-xs font-bold text-slate-300">نحوه قرارگیری طرح روی کارت:</label>
                                    <div class="grid grid-cols-2 gap-2.5" role="radiogroup" aria-label="نحوه قرارگیری طرح روی کارت">
                                        {{-- Option 1: Standard / Centered --}}
                                        <button
                                            type="button"
                                            wire:click="setCustomLayoutMode('centered')"
                                            role="radio"
                                            aria-checked="{{ $customLayoutMode === 'centered' ? 'true' : 'false' }}"
                                            class="min-h-[44px] flex flex-col p-3 rounded-2xl border text-start transition duration-150 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ffde5b] {{ $customLayoutMode === 'centered' ? 'border-[#ffde5b] bg-[#ffde5b]/10 shadow-md shadow-[#ffde5b]/10' : 'border-[#152244] bg-[#010619] hover:border-slate-700' }}"
                                        >
                                            <div class="flex items-center justify-between w-full mb-1.5">
                                                <span class="text-xs font-bold {{ $customLayoutMode === 'centered' ? 'text-[#ffde5b]' : 'text-slate-200' }}">
                                                    طرح استاندارد (مرکزی)
                                                </span>
                                                <span class="h-4 w-4 rounded-full border flex items-center justify-center shrink-0 {{ $customLayoutMode === 'centered' ? 'border-[#ffde5b] bg-[#ffde5b]' : 'border-slate-600' }}">
                                                    @if ($customLayoutMode === 'centered')
                                                        <span class="h-1.5 w-1.5 rounded-full bg-[#010619]"></span>
                                                    @endif
                                                </span>
                                            </div>
                                            <p class="text-[11px] leading-relaxed {{ $customLayoutMode === 'centered' ? 'text-slate-300' : 'text-slate-400' }}">
                                                اندازه متناسب در مرکز کارت با حفظ حاشیه امن
                                            </p>
                                        </button>

                                        {{-- Option 2: Full Card --}}
                                        <button
                                            type="button"
                                            wire:click="setCustomLayoutMode('full_card')"
                                            role="radio"
                                            aria-checked="{{ $customLayoutMode === 'full_card' ? 'true' : 'false' }}"
                                            class="min-h-[44px] flex flex-col p-3 rounded-2xl border text-start transition duration-150 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ffde5b] {{ $customLayoutMode === 'full_card' ? 'border-[#ffde5b] bg-[#ffde5b]/10 shadow-md shadow-[#ffde5b]/10' : 'border-[#152244] bg-[#010619] hover:border-slate-700' }}"
                                        >
                                            <div class="flex items-center justify-between w-full mb-1.5">
                                                <span class="text-xs font-bold {{ $customLayoutMode === 'full_card' ? 'text-[#ffde5b]' : 'text-slate-200' }}">
                                                    طرح سرتاسری (پوشش کامل)
                                                </span>
                                                <span class="h-4 w-4 rounded-full border flex items-center justify-center shrink-0 {{ $customLayoutMode === 'full_card' ? 'border-[#ffde5b] bg-[#ffde5b]' : 'border-slate-600' }}">
                                                    @if ($customLayoutMode === 'full_card')
                                                        <span class="h-1.5 w-1.5 rounded-full bg-[#010619]"></span>
                                                    @endif
                                                </span>
                                            </div>
                                            <p class="text-[11px] leading-relaxed {{ $customLayoutMode === 'full_card' ? 'text-slate-300' : 'text-slate-400' }}">
                                                پوشش کامل سطح کارت تا لبه‌ها
                                            </p>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
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
        <div class="lg:col-span-7 min-w-0 p-6 sm:p-8 bg-[#010619] flex flex-col items-center justify-between space-y-6 pb-10">
            <div class="w-full flex flex-col items-center space-y-6">
                {{-- Preview Header & Controls --}}
                <div class="w-full flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <h3 class="text-xs font-bold text-slate-300">
                            @if ($step === 1)
                                @if ($workflow === $fuelCardWorkflow)
                                    پیش‌نمایش کارت سوخت
                                @else
                                    انتخاب رنگ ورقه فلزی کارت
                                @endif
                            @else
                                نمای پشت کارت فلزی سفارش
                            @endif
                        </h3>

                        @if ($designSource === 'custom' && $customDesignToken)
                            <div class="inline-flex items-center gap-1.5 rounded-full bg-[#ffde5b]/10 border border-[#ffde5b]/30 px-2.5 py-1 text-[11px] font-bold text-[#ffde5b]">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#ffde5b] animate-pulse"></span>
                                <span>پیش‌نمایش طرح اختصاصی</span>
                            </div>
                        @endif
                    </div>

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
                    $isFullCard = ($selectedDesignImage['layout_mode'] ?? 'centered') === 'full_card';
                @endphp

                <div class="w-full max-w-md aspect-[1.586/1] rounded-2xl p-6 shadow-2xl relative overflow-hidden transition-all duration-300 border border-white/10 bg-gradient-to-br {{ $bgGradient }}">
                    {{-- Subtle Card Metallic Shine --}}
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-white/0 via-white/10 to-white/0 opacity-60"></div>

                    @if ($activeView === 'front')
                        {{-- FRONT CARD PREVIEW --}}
                        {{-- Design Overlay Image (Custom Upload OR Catalog Selection) --}}
                        @if ($designSource === 'custom' && $customDesignPreviewUrl)
                            @if ($customLayoutMode === 'full_card')
                                <div class="pointer-events-none absolute inset-0 z-0 opacity-85 overflow-hidden">
                                    <img src="{{ $customDesignPreviewUrl }}" alt="پیش‌نمایش طرح اختصاصی" class="h-full w-full object-cover object-center">
                                </div>
                            @else
                                <div class="pointer-events-none absolute inset-0 flex items-center justify-center p-8 z-0 opacity-80">
                                    <img src="{{ $customDesignPreviewUrl }}" alt="پیش‌نمایش طرح اختصاصی" class="max-h-full max-w-full object-contain">
                                </div>
                            @endif
                        @elseif ($selectedDesignImage && $selectedDesignImage['image_path'])
                            @if ($isFullCard)
                                <div class="pointer-events-none absolute inset-0 z-0 opacity-80 overflow-hidden">
                                    <img src="{{ asset('storage/' . $selectedDesignImage['image_path']) }}" alt="Laser Overlay" class="h-full w-full object-cover object-center">
                                </div>
                            @else
                                <div class="pointer-events-none absolute inset-0 flex items-center justify-center p-8 z-0 opacity-40">
                                    <img src="{{ asset('storage/' . $selectedDesignImage['image_path']) }}" alt="Laser Overlay" class="max-h-full max-w-full object-contain">
                                </div>
                            @endif
                        @endif

                        <div class="relative h-full flex flex-col justify-between items-center z-10">
                            {{-- Decorative Sample Chip Element for Fuel Card FRONT --}}
                            @if ($workflow === $fuelCardWorkflow)
                                <div class="absolute top-1/2 -translate-y-1/2 left-6 h-9 w-12 rounded-md bg-gradient-to-br from-amber-300 via-yellow-400 to-amber-600 border border-yellow-100/70 shadow-md flex items-center justify-center pointer-events-none opacity-95 z-10">
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
                                        <tr class="border-b border-white/20">
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
    <footer class="sticky bottom-0 z-30 border-t border-[#152244] bg-[#070e24]/95 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 backdrop-blur-md shadow-2xl">
        {{-- Navigation Actions --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
            @if ($step === 1)
                <button
                    type="button"
                    wire:click="setStep(2)"
                    wire:loading.attr="disabled"
                    wire:target="customImage"
                    class="min-h-[44px] inline-flex items-center justify-center gap-2 rounded-xl bg-[#ffde5b] px-6 py-3 w-full sm:w-auto text-xs font-bold text-[#010619] transition duration-200 hover:bg-[#f5d347] active:scale-[0.99] shadow-md shadow-[#ffde5b]/20 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
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
                    class="min-h-[44px] inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800 px-5 py-3 w-full sm:w-auto text-xs font-bold text-slate-300 transition hover:bg-slate-700 hover:text-white border border-slate-700 cursor-pointer"
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
                    class="min-h-[44px] inline-flex items-center justify-center gap-2 rounded-xl bg-[#ffde5b] px-6 py-3 w-full sm:w-auto text-xs font-bold text-[#010619] transition duration-200 hover:bg-[#f5d347] active:scale-[0.99] shadow-md shadow-[#ffde5b]/20 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
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
            {{ fa_digits($step) }} / ۲
        </div>

        {{-- Dynamic Server-side Total Price Display --}}
        <div class="text-end">
            <span class="text-xs text-slate-400 block">مبلغ قابل پرداخت:</span>
            <span class="text-lg font-black text-[#ffde5b] tracking-tight">
                {{ fa_digits(number_format($totalPrice)) }} <span class="text-xs font-normal text-slate-400">تومان</span>
            </span>
        </div>
    </footer>
</div>