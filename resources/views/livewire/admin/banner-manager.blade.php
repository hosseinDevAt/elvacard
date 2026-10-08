<div>
    <x-admin.page-header title="مدیریت بنرهای صفحه اصلی" subtitle="مدیریت پویا، چیدمان، تصاویر دسکتاپ و موبایل، محتوای متنی و رنگ‌بندی اسلایدر">
        <x-slot:actions>
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                <a href="{{ route('admin.appearance') }}" class="admin-btn admin-btn-secondary text-xs">
                    بازگشت به ظاهر سایت
                </a>
                <button type="button" wire:click="addBanner" class="admin-btn bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold shadow-sm">
                    <span>+ افزودن بنر جدید</span>
                </button>
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
                    <strong class="font-bold">راهنمای اسلایدر پویا:</strong>
                    می‌توانید هر تعداد بنر که نیاز دارید ایجاد کنید. ترتیب نمایش با دکمه‌های «↑» و «↓» قابل جابجایی است. در صورت وجود تنها ۱ بنر، اسلایدر به صورت تک‌بنر ثابت و بدون دکمه‌های ناوبری اضافی رندر می‌شود. اگر تمام بنرها حذف شوند، صفحه اصلی بدون اسلایدر نمایش خواهد یافت.
                </div>
            </div>
        </div>

        @if (empty($banners))
            <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 mb-4">
                    <x-icons.image class="h-7 w-7" />
                </div>
                <h4 class="text-base font-extrabold text-slate-800 mb-1">هنوز بنری ایجاد نشده است</h4>
                <p class="text-xs text-slate-500 mb-6 max-w-sm mx-auto">در حال حاضر هیچ بنری در صفحه اصلی فعال نیست. می‌توانید با کلیک روی دکمه زیر، اولین بنر را اضافه کنید.</p>
                <button type="button" wire:click="addBanner" class="admin-btn admin-btn-primary font-bold shadow-md shadow-[#ffde5b]/25">
                    <span>+ افزودن بنر</span>
                </button>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($banners as $index => $banner)
                    @php
                        $bannerId = $banner['id'];
                        $hasDesktopUpload = isset($desktopUploads[$bannerId]);
                        $hasDesktopPath = ! empty($banner['image_path']);
                        $hasMobileUpload = isset($mobileUploads[$bannerId]);
                        $hasMobilePath = ! empty($banner['mobile_image_path']);
                    @endphp

                    <section class="admin-card p-6 border border-slate-200/90 shadow-sm transition" wire:key="banner-card-{{ $bannerId }}">
                        {{-- Header with Order controls & Delete --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-5 border-b border-slate-100 gap-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-900 text-[#ffde5b] font-black text-xs">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <h3 class="text-sm font-extrabold text-slate-900">
                                        بنر شماره {{ $index + 1 }}
                                        @if ($index === 0)
                                            <span class="text-[11px] font-normal text-slate-500">(اولین اسلاید / ورودی اصلی)</span>
                                        @endif
                                    </h3>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                {{-- Status Badges --}}
                                @if ($hasDesktopPath || $hasDesktopUpload)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 border border-emerald-200">
                                        دسکتاپ
                                    </span>
                                @endif
                                @if ($hasMobilePath || $hasMobileUpload)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200">
                                        موبایل
                                    </span>
                                @endif

                                {{-- Reorder Controls --}}
                                <div class="flex items-center bg-slate-100 rounded-lg p-0.5 border border-slate-200">
                                    <button type="button" wire:click="moveUp({{ $index }})" @disabled($index === 0)
                                            title="انتقال به بالا (↑)"
                                            class="p-1 rounded text-slate-600 hover:text-slate-900 hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    </button>
                                    <button type="button" wire:click="moveDown({{ $index }})" @disabled($index === count($banners) - 1)
                                            title="انتقال به پایین (↓)"
                                            class="p-1 rounded text-slate-600 hover:text-slate-900 hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Remove Banner Button --}}
                                <button type="button" wire:click="removeBanner({{ $index }})" wire:confirm="آیا از حذف کامل این بنر مطمئن هستید؟"
                                        class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-2.5 py-1.5 rounded-lg font-medium transition cursor-pointer">
                                    <x-icons.trash class="h-3.5 w-3.5" />
                                    <span>حذف بنر</span>
                                </button>
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

                                <input type="file" wire:model="desktopUploads.{{ $bannerId }}" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
                                @error("desktopUploads.{$bannerId}") <p class="admin-error">{{ $message }}</p> @enderror

                                @if ($hasDesktopUpload)
                                    <div class="mt-3 flex items-center gap-3">
                                        <img src="{{ $desktopUploads[$bannerId]->temporaryUrl() }}" alt="پیش‌نمایش دسکتاپ {{ $index + 1 }}" class="h-24 w-48 object-cover rounded-xl border border-slate-200 shadow-sm">
                                        <button type="button" wire:click="$set('desktopUploads.{{ $bannerId }}', null)" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition cursor-pointer">انصراف از آپلود</button>
                                    </div>
                                @elseif ($hasDesktopPath)
                                    <div class="mt-3 flex items-center gap-3">
                                        <img src="{{ asset('storage/' . $banner['image_path']) }}" alt="دسکتاپ فعلی {{ $index + 1 }}" class="h-24 w-48 object-cover rounded-xl border border-slate-200 shadow-sm">
                                        <button type="button" wire:click="removeDesktopImage({{ $index }})" wire:confirm="آیا از حذف تصویر دسکتاپ این بنر مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition cursor-pointer">حذف تصویر دسکتاپ</button>
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

                                <input type="file" wire:model="mobileUploads.{{ $bannerId }}" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
                                @error("mobileUploads.{$bannerId}") <p class="admin-error">{{ $message }}</p> @enderror

                                @if ($hasMobileUpload)
                                    <div class="mt-3 flex items-center gap-3">
                                        <img src="{{ $mobileUploads[$bannerId]->temporaryUrl() }}" alt="پیش‌نمایش موبایل {{ $index + 1 }}" class="h-24 w-24 object-cover rounded-xl border border-slate-200 shadow-sm">
                                        <button type="button" wire:click="$set('mobileUploads.{{ $bannerId }}', null)" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition cursor-pointer">انصراف از آپلود</button>
                                    </div>
                                @elseif ($hasMobilePath)
                                    <div class="mt-3 flex items-center gap-3">
                                        <img src="{{ asset('storage/' . $banner['mobile_image_path']) }}" alt="موبایل فعلی {{ $index + 1 }}" class="h-24 w-24 object-cover rounded-xl border border-slate-200 shadow-sm">
                                        <button type="button" wire:click="removeMobileImage({{ $index }})" wire:confirm="آیا از حذف تصویر موبایل این بنر مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition cursor-pointer">حذف تصویر موبایل</button>
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
                                <input type="text" wire:model.debounce.300ms="banners.{{ $index }}.title" placeholder="مثلاً: کارت شخصی فلزی" class="admin-input">
                                @error("banners.{$index}.title") <p class="admin-error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="admin-label">زیرعنوان اسلاید</label>
                                <input type="text" wire:model.debounce.300ms="banners.{{ $index }}.subtitle" placeholder="مثلاً: طراحی و تولید کارت‌های فلزی منحصربه‌فرد" class="admin-input">
                                @error("banners.{$index}.subtitle") <p class="admin-error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="admin-label">متن دکمه اقدام (CTA)</label>
                                <input type="text" wire:model.debounce.300ms="banners.{{ $index }}.cta_text" placeholder="مثلاً: مشاهده محصولات" class="admin-input">
                                @error("banners.{$index}.cta_text") <p class="admin-error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="admin-label">لینک دکمه اقدام (URL)</label>
                                <input type="text" wire:model.debounce.300ms="banners.{{ $index }}.cta_url" placeholder="https://example.com یا /catalog/products" dir="ltr" class="admin-input font-mono text-xs">
                                @error("banners.{$index}.cta_url") <p class="admin-error">{{ $message }}</p> @enderror
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
                                        <input type="color" wire:model.live="banners.{{ $index }}.text_color" class="h-9 w-10 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-0.5">
                                        <input type="text" wire:model.debounce.300ms="banners.{{ $index }}.text_color" dir="ltr" maxlength="7" placeholder="#ffffff" class="admin-input font-mono text-xs uppercase flex-1">
                                    </div>
                                    @error("banners.{$index}.text_color") <p class="admin-error">{{ $message }}</p> @enderror
                                    <div class="mt-2 flex items-center gap-1.5 text-[10px]">
                                        <span class="text-slate-400">پیش‌فرض:</span>
                                        <button type="button" wire:click="$set('banners.{{ $index }}.text_color', '#ffffff')" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono">#FFFFFF</button>
                                        <button type="button" wire:click="$set('banners.{{ $index }}.text_color', '#ffde5b')" class="px-1.5 py-0.5 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 font-mono">#FFDE5B</button>
                                        <button type="button" wire:click="$set('banners.{{ $index }}.text_color', '#010619')" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-900 text-white font-mono">#010619</button>
                                    </div>
                                </div>

                                {{-- Button BG Color --}}
                                <div class="rounded-xl border border-slate-200/80 p-3 bg-white">
                                    <label class="admin-label text-[11px]">رنگ پس‌زمینه دکمه (CTA)</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" wire:model.live="banners.{{ $index }}.button_bg_color" class="h-9 w-10 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-0.5">
                                        <input type="text" wire:model.debounce.300ms="banners.{{ $index }}.button_bg_color" dir="ltr" maxlength="7" placeholder="#ffde5b" class="admin-input font-mono text-xs uppercase flex-1">
                                    </div>
                                    @error("banners.{$index}.button_bg_color") <p class="admin-error">{{ $message }}</p> @enderror
                                    <div class="mt-2 flex items-center gap-1.5 text-[10px]">
                                        <span class="text-slate-400">پیش‌فرض:</span>
                                        <button type="button" wire:click="$set('banners.{{ $index }}.button_bg_color', '#ffde5b')" class="px-1.5 py-0.5 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 font-mono">#FFDE5B</button>
                                        <button type="button" wire:click="$set('banners.{{ $index }}.button_bg_color', '#010619')" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-900 text-white font-mono">#010619</button>
                                        <button type="button" wire:click="$set('banners.{{ $index }}.button_bg_color', '#ffffff')" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono">#FFFFFF</button>
                                    </div>
                                </div>

                                {{-- Button Text Color --}}
                                <div class="rounded-xl border border-slate-200/80 p-3 bg-white">
                                    <label class="admin-label text-[11px]">رنگ متن و آیکون دکمه</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" wire:model.live="banners.{{ $index }}.button_text_color" class="h-9 w-10 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-0.5">
                                        <input type="text" wire:model.debounce.300ms="banners.{{ $index }}.button_text_color" dir="ltr" maxlength="7" placeholder="#010619" class="admin-input font-mono text-xs uppercase flex-1">
                                    </div>
                                    @error("banners.{$index}.button_text_color") <p class="admin-error">{{ $message }}</p> @enderror
                                    <div class="mt-2 flex items-center gap-1.5 text-[10px]">
                                        <span class="text-slate-400">پیش‌فرض:</span>
                                        <button type="button" wire:click="$set('banners.{{ $index }}.button_text_color', '#010619')" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-900 text-white font-mono">#010619</button>
                                        <button type="button" wire:click="$set('banners.{{ $index }}.button_text_color', '#ffffff')" class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono">#FFFFFF</button>
                                    </div>
                                </div>
                            </div>

                            {{-- Live Micro-Preview --}}
                            @php
                                $previewTextColor = filled($banner['text_color'] ?? null) && preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $banner['text_color']) ? $banner['text_color'] : '#ffffff';
                                $previewBtnBg = filled($banner['button_bg_color'] ?? null) && preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $banner['button_bg_color']) ? $banner['button_bg_color'] : '#ffde5b';
                                $previewBtnText = filled($banner['button_text_color'] ?? null) && preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $banner['button_text_color']) ? $banner['button_text_color'] : '#010619';
                                $sampleTitle = filled($banner['title'] ?? null) ? $banner['title'] : 'عنوان نمونه بنر';
                                $sampleSubtitle = filled($banner['subtitle'] ?? null) ? $banner['subtitle'] : 'توضیحات و زیرعنوان نمونه اسلایدر';
                                $sampleCta = filled($banner['cta_text'] ?? null) ? $banner['cta_text'] : 'دکمه اقدام';
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
                @endforeach
            </div>
        @endif

        <div class="sticky bottom-4 z-10 flex items-center justify-between bg-white/90 backdrop-blur-md p-4 rounded-2xl border border-slate-200 shadow-lg">
            <button type="button" wire:click="addBanner" class="admin-btn bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold shadow-sm">
                <span>+ افزودن یک بنر دیگر</span>
            </button>
            <button type="submit" class="admin-btn admin-btn-primary px-8 py-2.5 font-bold shadow-lg shadow-[#ffde5b]/30" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">ذخیره تمام بنرها</span>
                <span wire:loading wire:target="save">در حال ذخیره...</span>
            </button>
        </div>
    </form>
</div>
