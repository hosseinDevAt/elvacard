<div>
    <x-admin.page-header title="مدیریت بنرهای صفحه اصلی" subtitle="مدیریت تصاویر دسکتاپ و موبایل، محتوای متنی، دکمه‌های اقدام و رنگ‌بندی اسلایدر">
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.appearance') }}" class="admin-btn admin-btn-secondary text-xs">
                    بازگشت به ظاهر سایت
                </a>
                <button type="submit" form="banners-form" class="admin-btn admin-btn-primary font-semibold shadow-md shadow-[#ffde5b]/25" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">ذخیره تغییرات</span>
                    <span wire:loading wire:target="save">در حال ذخیره...</span>
                </button>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('success'))
        <div class="mb-6 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <x-icons.check-badge class="mt-0.5 shrink-0 text-emerald-600" />
            <div class="min-w-0">{{ session('success') }}</div>
        </div>
    @endif

    <form id="banners-form" wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-amber-200/80 bg-amber-50/60 p-4 text-xs text-amber-900 leading-relaxed">
            <div class="flex items-start gap-2">
                <x-icons.sparkles class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                <div>
                    <strong class="font-bold">راهنمای اسلایدر ریسپانسیو:</strong>
                    برای هر اسلاید می‌توانید یک تصویر عریض برای دسکتاپ و یک تصویر عمودی/مربعی مخصوص موبایل بارگذاری کنید. اگر تصویر موبایل تنظیم نشود، سیستم به‌صورت خودکار همان تصویر دسکتاپ را به کار می‌برد. چنانچه فیلدهای متنی بنر را خالی بگذارید، بنر صرفاً به‌صورت تصویری خالص (بدون نوشته یا لایه تیره) رندر خواهد شد.
                </div>
            </div>
        </div>

        @for ($i = 1; $i <= 3; $i++)
            <section class="admin-card p-6 border border-slate-200/90 shadow-sm" wire:key="banner-slot-{{ $i }}">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-5 border-b border-slate-100 gap-2">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-900 text-[#ffde5b] font-black text-xs">
                            {{ $i }}
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">
                                اسلاید شماره {{ $i }}
                                @if ($i === 1)
                                    <span class="text-[11px] font-normal text-slate-500">(اسلاید اول / پیش‌فرض)</span>
                                @endif
                            </h3>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if (${"bannerImagePath{$i}"})
                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 border border-emerald-200">
                                دسکتاپ فعال
                            </span>
                        @endif
                        @if (${"bannerMobileImagePath{$i}"})
                            <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200">
                                موبایل اختصاصی
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                موبایل: پیش‌فرض دسکتاپ
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Responsive Images Section --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 pb-6 border-b border-slate-100">
                    {{-- Desktop Image --}}
                    <div class="rounded-xl border border-slate-200/80 p-4 bg-slate-50/50">
                        <div class="flex items-center justify-between mb-2">
                            <label class="admin-label !mb-0 font-bold text-slate-800">تصویر نسخه دسکتاپ (افقی)</label>
                            <span class="text-[11px] text-slate-500 font-mono">پیشنهادی: ۱۹۲۰×۶۰۰ پیکسل</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">حداکثر ۲ مگابایت (فرمت‌های JPG, PNG, WEBP)</p>

                        <input type="file" wire:model="bannerImage{{ $i }}" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
                        @error("bannerImage{$i}") <p class="admin-error">{{ $message }}</p> @enderror

                        @if (${"bannerImage{$i}"})
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ ${"bannerImage{$i}"}->temporaryUrl() }}" alt="پیش‌نمایش دسکتاپ {{ $i }}" class="h-24 w-48 object-cover rounded-xl border border-slate-200 shadow-sm">
                                <button type="button" wire:click="$set('bannerImage{{ $i }}', null)" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition cursor-pointer">انصراف از آپلود</button>
                            </div>
                        @elseif (${"bannerImagePath{$i}"})
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ asset('storage/' . ${"bannerImagePath{$i}"}) }}" alt="دسکتاپ فعلی {{ $i }}" class="h-24 w-48 object-cover rounded-xl border border-slate-200 shadow-sm">
                                <button type="button" wire:click="removeBannerImage({{ $i }})" wire:confirm="آیا از حذف تصویر دسکتاپ این بنر مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition cursor-pointer">حذف تصویر دسکتاپ</button>
                            </div>
                        @else
                            <div class="mt-3 rounded-lg border border-dashed border-slate-200 bg-white p-3 text-center text-[11px] text-slate-400">
                                بدون تصویر دسکتاپ (گرادیان سیستمی اعمال خواهد شد)
                            </div>
                        @endif
                    </div>

                    {{-- Mobile Image --}}
                    <div class="rounded-xl border border-slate-200/80 p-4 bg-slate-50/50">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <label class="admin-label !mb-0 font-bold text-slate-800">تصویر نسخه موبایل (عمودی/مربعی)</label>
                                <span class="rounded bg-slate-200/80 px-1.5 py-0.5 text-[10px] text-slate-700 font-medium">اختیاری</span>
                            </div>
                            <span class="text-[11px] text-slate-500 font-mono">پیشنهادی: ۷۵۰×۷۵۰ یا ۷۵۰×۱۰۰۰</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">در صورت خالی بودن، تصویر دسکتاپ در موبایل نیز استفاده می‌شود.</p>

                        <input type="file" wire:model="bannerMobileImage{{ $i }}" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
                        @error("bannerMobileImage{$i}") <p class="admin-error">{{ $message }}</p> @enderror

                        @if (${"bannerMobileImage{$i}"})
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ ${"bannerMobileImage{$i}"}->temporaryUrl() }}" alt="پیش‌نمایش موبایل {{ $i }}" class="h-24 w-24 object-cover rounded-xl border border-slate-200 shadow-sm">
                                <button type="button" wire:click="$set('bannerMobileImage{{ $i }}', null)" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition cursor-pointer">انصراف از آپلود</button>
                            </div>
                        @elseif (${"bannerMobileImagePath{$i}"})
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ asset('storage/' . ${"bannerMobileImagePath{$i}"}) }}" alt="موبایل فعلی {{ $i }}" class="h-24 w-24 object-cover rounded-xl border border-slate-200 shadow-sm">
                                <button type="button" wire:click="removeBannerMobileImage({{ $i }})" wire:confirm="آیا از حذف تصویر موبایل این بنر مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition cursor-pointer">حذف تصویر موبایل</button>
                            </div>
                        @else
                            <div class="mt-3 rounded-lg border border-dashed border-slate-200 bg-white p-3 text-center text-[11px] text-slate-400">
                                بدون تصویر اختصاصی موبایل (تصویر دسکتاپ لود می‌شود)
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Content Section (Title, Subtitle, CTA) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 pb-6 border-b border-slate-100">
                    <div>
                        <label class="admin-label">عنوان اسلاید</label>
                        <input type="text" wire:model.debounce.300ms="bannerTitle{{ $i }}" placeholder="مثلاً: کارت شخصی فلزی" class="admin-input">
                        @error("bannerTitle{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label">زیرعنوان اسلاید</label>
                        <input type="text" wire:model.debounce.300ms="bannerSubtitle{{ $i }}" placeholder="مثلاً: طراحی و تولید کارت‌های فلزی منحصربه‌فرد" class="admin-input">
                        @error("bannerSubtitle{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label">متن دکمه اقدام (CTA)</label>
                        <input type="text" wire:model.debounce.300ms="bannerCtaText{{ $i }}" placeholder="مثلاً: مشاهده محصولات" class="admin-input">
                        @error("bannerCtaText{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label">لینک دکمه اقدام (URL)</label>
                        <input type="text" wire:model.debounce.300ms="bannerCtaUrl{{ $i }}" placeholder="https://example.com یا /catalog/products" dir="ltr" class="admin-input font-mono text-xs">
                        @error("bannerCtaUrl{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Colors Section --}}
                <div>
                    <h4 class="text-xs font-bold text-slate-800 mb-3">تنظیمات رنگ‌بندی اسلاید</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Text Color --}}
                        <div class="rounded-xl border border-slate-200/80 p-3 bg-white">
                            <label class="admin-label text-[11px]">رنگ متن (عنوان و زیرعنوان)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.live="bannerTextColor{{ $i }}" class="h-9 w-10 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-0.5">
                                <input type="text" wire:model.debounce.300ms="bannerTextColor{{ $i }}" dir="ltr" maxlength="7" placeholder="#ffffff" class="admin-input font-mono text-xs uppercase flex-1">
                            </div>
                            @error("bannerTextColor{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                            <div class="mt-2 flex items-center gap-1.5 text-[10px]">
                                <span class="text-slate-400">پیش‌فرض:</span>
                                <button type="button" wire:click="$set('bannerTextColor{{ $i }}', '#ffffff')" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono">#FFFFFF</button>
                                <button type="button" wire:click="$set('bannerTextColor{{ $i }}', '#ffde5b')" class="px-1.5 py-0.5 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 font-mono">#FFDE5B</button>
                                <button type="button" wire:click="$set('bannerTextColor{{ $i }}', '#010619')" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-900 text-white font-mono">#010619</button>
                            </div>
                        </div>

                        {{-- Button BG Color --}}
                        <div class="rounded-xl border border-slate-200/80 p-3 bg-white">
                            <label class="admin-label text-[11px]">رنگ پس‌زمینه دکمه (CTA)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.live="bannerButtonBgColor{{ $i }}" class="h-9 w-10 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-0.5">
                                <input type="text" wire:model.debounce.300ms="bannerButtonBgColor{{ $i }}" dir="ltr" maxlength="7" placeholder="#ffde5b" class="admin-input font-mono text-xs uppercase flex-1">
                            </div>
                            @error("bannerButtonBgColor{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                            <div class="mt-2 flex items-center gap-1.5 text-[10px]">
                                <span class="text-slate-400">پیش‌فرض:</span>
                                <button type="button" wire:click="$set('bannerButtonBgColor{{ $i }}', '#ffde5b')" class="px-1.5 py-0.5 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 font-mono">#FFDE5B</button>
                                <button type="button" wire:click="$set('bannerButtonBgColor{{ $i }}', '#010619')" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-900 text-white font-mono">#010619</button>
                                <button type="button" wire:click="$set('bannerButtonBgColor{{ $i }}', '#ffffff')" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono">#FFFFFF</button>
                            </div>
                        </div>

                        {{-- Button Text Color --}}
                        <div class="rounded-xl border border-slate-200/80 p-3 bg-white">
                            <label class="admin-label text-[11px]">رنگ متن و آیکون دکمه</label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.live="bannerButtonTextColor{{ $i }}" class="h-9 w-10 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-0.5">
                                <input type="text" wire:model.debounce.300ms="bannerButtonTextColor{{ $i }}" dir="ltr" maxlength="7" placeholder="#010619" class="admin-input font-mono text-xs uppercase flex-1">
                            </div>
                            @error("bannerButtonTextColor{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                            <div class="mt-2 flex items-center gap-1.5 text-[10px]">
                                <span class="text-slate-400">پیش‌فرض:</span>
                                <button type="button" wire:click="$set('bannerButtonTextColor{{ $i }}', '#010619')" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-900 text-white font-mono">#010619</button>
                                <button type="button" wire:click="$set('bannerButtonTextColor{{ $i }}', '#ffffff')" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono">#FFFFFF</button>
                            </div>
                        </div>
                    </div>

                    {{-- Live Micro-Preview --}}
                    @php
                        $previewTextColor = filled(${"bannerTextColor{$i}"}) && preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', ${"bannerTextColor{$i}"}) ? ${"bannerTextColor{$i}"} : '#ffffff';
                        $previewBtnBg = filled(${"bannerButtonBgColor{$i}"}) && preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', ${"bannerButtonBgColor{$i}"}) ? ${"bannerButtonBgColor{$i}"} : '#ffde5b';
                        $previewBtnText = filled(${"bannerButtonTextColor{$i}"}) && preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', ${"bannerButtonTextColor{$i}"}) ? ${"bannerButtonTextColor{$i}"} : '#010619';
                        $sampleTitle = ${"bannerTitle{$i}"} ?: 'عنوان نمونه بنر';
                        $sampleSubtitle = ${"bannerSubtitle{$i}"} ?: 'توضیحات و زیرعنوان نمونه اسلایدر';
                        $sampleCta = ${"bannerCtaText{$i}"} ?: 'دکمه اقدام';
                    @endphp
                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-900 p-4 text-center">
                        <span class="text-[10px] text-slate-400 font-medium block mb-2">پیش‌نمایش ترکیب رنگ در پس‌زمینه تیره:</span>
                        <div class="text-sm font-bold mb-1" style="color: {{ $previewTextColor }};">
                            {{ $sampleTitle }}
                        </div>
                        <div class="text-xs mb-3" style="color: {{ $previewTextColor }}; opacity: 0.85;">
                            {{ $sampleSubtitle }}
                        </div>
                        <div class="inline-flex items-center gap-1.5 rounded-lg px-4 py-1.5 text-xs font-bold shadow" style="background-color: {{ $previewBtnBg }}; color: {{ $previewBtnText }};">
                            <span>{{ $sampleCta }}</span>
                            <x-icons.arrow-left class="h-3.5 w-3.5" style="color: {{ $previewBtnText }};" />
                        </div>
                    </div>
                </div>
            </section>
        @endfor

        <div class="sticky bottom-4 z-10 flex justify-end">
            <button type="submit" class="admin-btn admin-btn-primary px-8 py-3 font-bold shadow-lg shadow-[#ffde5b]/30" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">ذخیره تمام بنرها</span>
                <span wire:loading wire:target="save">در حال ذخیره...</span>
            </button>
        </div>
    </form>
</div>
