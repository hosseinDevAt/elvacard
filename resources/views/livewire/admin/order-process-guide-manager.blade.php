<div>
    <x-admin.page-header
        title="مدیریت راهنمای مراحل سفارش"
        description="مدیریت مراحل شفاف‌سازی فرآیند ساخت و ارسال کارت‌های بانکی فلزی و کارت سوخت"
    >
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <a href="{{ route('order-process.index') }}" target="_blank" class="admin-btn admin-btn-secondary">
                    <x-icons.globe class="w-4 h-4" />
                    <span>مشاهده راهنما در سایت</span>
                </a>
                <button type="button" wire:click="createStage" class="admin-btn admin-btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>افزودن مرحله جدید</span>
                </button>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Workflow Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        @foreach($workflowLabels as $wfKey => $wfLabel)
            @php
                $isActiveWf = $workflow === $wfKey;
            @endphp
            <button type="button"
                    wire:click="switchWorkflow('{{ $wfKey }}')"
                    class="relative inline-flex items-center gap-2.5 rounded-xl px-5 py-2.5 text-sm font-bold transition-all {{ $isActiveWf ? 'bg-[#010619] text-[#ffde5b] shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200' }}">
                <span>{{ $wfLabel }}</span>
                @if($wfKey === $workflow)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $isPublished ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-300' }}">
                        {{ $isPublished ? 'منتشر شده' : 'پیش‌نویس' }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    @if(! $tablesReady)
        <div class="admin-card p-6 mb-6 border-amber-300 bg-amber-50">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="text-base font-bold text-amber-900">جداول پایگاه داده ایجاد نشده‌اند</h3>
                    <p class="text-xs sm:text-sm text-amber-800 mt-1 leading-relaxed">
                        جداول مربوط به راهنمای مراحل سفارش (order_process_guides و order_process_stages) هنوز در پایگاه داده ایجاد نشده‌اند. برای فعال‌سازی کامل امکان ویرایش و ذخیره، لطفاً مایگریشن مربوطه را با دستور <code class="font-mono bg-amber-200/60 px-1.5 py-0.5 rounded text-amber-900">php artisan migrate</code> اجرا نمایید.
                    </p>
                </div>
            </div>
        </div>
    @else
    <div class="admin-card p-6 mb-6">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-base">تنظیمات کلی راهنما: {{ $workflowLabels[$workflow] ?? $workflow }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">عنوان و مقدمه این جریان که در بالای مراحل به کاربر نمایش داده می‌شود</p>
            </div>
            <button type="button"
                    wire:click="togglePublish"
                    class="admin-btn admin-btn-sm {{ $isPublished ? 'admin-btn-secondary text-amber-700' : 'admin-btn-primary' }}">
                @if($isPublished)
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                    </svg>
                    <span>تغییر به پیش‌نویس (عدم نمایش عمومی)</span>
                @else
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span>انتشار عمومی این راهنما</span>
                @endif
            </button>
        </div>

        <form wire:submit="saveGuide" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="admin-label">عنوان راهنما</label>
                    <input type="text" wire:model="guideTitle" class="admin-input" placeholder="مثال: مراحل سفارش کارت فلزی">
                    @error('guideTitle') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="isPublished" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b] h-4 w-4">
                        <span class="text-sm font-semibold text-slate-800">وضعیت انتشار عمومی</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="admin-label">توضیحات / مقدمه راهنما (اختیاری)</label>
                <textarea wire:model="guideDescription" rows="2" class="admin-input" placeholder="توضیح کوتاه درباره روند سفارش و مراحل تولید و ارسال"></textarea>
                @error('guideDescription') <p class="admin-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <span>ذخیره تغییرات کلی</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Stage Modal / Form --}}
    @if($showStageModal)
        <div class="admin-card p-6 mb-6 border-2 border-[#ffde5b]/30 bg-amber-50/20">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200">
                <h3 class="font-extrabold text-slate-900 text-base">
                    {{ $editingStageId ? 'ویرایش مرحله' : 'افزودن مرحله جدید به ' . ($workflowLabels[$workflow] ?? '') }}
                </h3>
                <button type="button" wire:click="cancelStageModal" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveStage" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="admin-label">عنوان مرحله <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="stageTitle" class="admin-input" placeholder="مثال: انتخاب و شخصی‌سازی کارت">
                        @error('stageTitle') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label">آیکون مرحله</label>
                        <select wire:model="stageIcon" class="admin-input">
                            @foreach($supportedIcons as $iconKey => $iconLabel)
                                <option value="{{ $iconKey }}">{{ $iconLabel }} ({{ $iconKey }})</option>
                            @endforeach
                        </select>
                        @error('stageIcon') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label">مدت زمان تقریبی (اختیاری)</label>
                        <input type="text" wire:model="stageDurationHint" class="admin-input" placeholder="مثال: ۱ روز کاری، آنی، و غیره">
                        @error('stageDurationHint') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label">شرح کامل مرحله <span class="text-rose-500">*</span></label>
                    <textarea wire:model="stageDescription" rows="4" class="admin-input" placeholder="توضیحات کامل این مرحله و اقدامات موردنیاز مشتری یا فروشگاه"></textarea>
                    @error('stageDescription') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                    <div class="flex items-center gap-6">
                        <div class="w-32">
                            <label class="admin-label">ترتیب نمایش</label>
                            <input type="number" min="0" wire:model="stageSortOrder" class="admin-input">
                            @error('stageSortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="pt-5">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="stageIsActive" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b] h-4 w-4">
                                <span class="text-sm font-semibold text-slate-800">مرحله فعال باشد</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-5">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <span>{{ $editingStageId ? 'به‌روزرسانی مرحله' : 'ثبت مرحله' }}</span>
                        </button>
                        <button type="button" wire:click="cancelStageModal" class="admin-btn admin-btn-secondary">
                            <span>انصراف</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    {{-- Stages Table --}}
    <div class="admin-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h4 class="font-bold text-slate-900 text-sm">لیست مراحل ({{ $stages->count() }} مرحله)</h4>
            <span class="text-xs text-slate-500">مراحل به ترتیب شماره نمایش و از بالا به پایین در صفحه عمومی قرار می‌گیرند</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16 text-center">ترتیب</th>
                        <th class="admin-th w-16 text-center">آیکون</th>
                        <th class="admin-th">عنوان و زمان تقریبی</th>
                        <th class="admin-th">توضیحات مرحله</th>
                        <th class="admin-th w-24 text-center">وضعیت</th>
                        <th class="admin-th w-28 text-center">جابجایی</th>
                        <th class="admin-th w-36 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stages as $index => $stage)
                        <tr class="hover:bg-slate-50/60 transition {{ ! $stage->is_active ? 'opacity-60 bg-slate-50/30' : '' }}">
                            <td class="admin-td text-center font-mono font-bold text-slate-600">
                                {{ $stage->sort_order }}
                            </td>
                            <td class="admin-td text-center">
                                @if($stage->icon)
                                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center mx-auto" title="{{ $stage->icon }}">
                                        <x-dynamic-component :component="'icons.' . $stage->icon" class="w-4 h-4" />
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="admin-td">
                                <div class="font-bold text-slate-900">{{ $stage->title }}</div>
                                @if($stage->duration_hint)
                                    <div class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 mt-0.5">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>{{ $stage->duration_hint }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="admin-td text-slate-600 text-xs max-w-md">
                                <div class="line-clamp-2 leading-relaxed">
                                    {{ $stage->description }}
                                </div>
                            </td>
                            <td class="admin-td text-center">
                                <button type="button"
                                        wire:click="toggleStageActive({{ $stage->id }})"
                                        class="admin-badge {{ $stage->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }} cursor-pointer hover:opacity-80 transition"
                                        title="برای تغییر وضعیت کلیک کنید">
                                    {{ $stage->is_active ? 'فعال' : 'غیرفعال' }}
                                </button>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                            wire:click="moveStageUp({{ $stage->id }})"
                                            @disabled($index === 0)
                                            class="p-1 rounded hover:bg-slate-200 text-slate-600 disabled:opacity-30 disabled:hover:bg-transparent"
                                            title="انتقال به بالا">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            wire:click="moveStageDown({{ $stage->id }})"
                                            @disabled($index === $stages->count() - 1)
                                            class="p-1 rounded hover:bg-slate-200 text-slate-600 disabled:opacity-30 disabled:hover:bg-transparent"
                                            title="انتقال به پایین">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button"
                                            wire:click="editStage({{ $stage->id }})"
                                            class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">
                                        ویرایش
                                    </button>
                                    <button type="button"
                                            wire:click="deleteStage({{ $stage->id }})"
                                            wire:confirm="آیا از حذف این مرحله اطمینان دارید؟"
                                            class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-2 py-1 transition">
                                        حذف
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8">
                                <x-admin.empty-state
                                    title="هیچ مرحله‌ای تعریف نشده است"
                                    description="با کلیک روی دکمه «افزودن مرحله جدید»، مراحل فرآیند این سفارش را به ترتیب اضافه فرمایید."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
