<div>
    <x-admin.page-header title="ظاهر و هویت برند" subtitle="مدیریت لوگو، فاوآیکون، بنرهای صفحه اصلی، اطلاعات فوتر و آیکون‌ها">
        <x-slot:actions>
            <button type="submit" form="appearance-form" class="admin-btn admin-btn-primary font-semibold shadow-md shadow-indigo-600/20">
                ذخیره تغییرات
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('success'))
        <div class="mb-6 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <x-icons.check-badge class="mt-0.5 shrink-0 text-emerald-600" />
            <div class="min-w-0">{{ session('success') }}</div>
        </div>
    @endif

    <form id="appearance-form" wire:submit="save" class="space-y-6">
        {{-- Identity --}}
        <section class="admin-card p-6">
            <h3 class="text-base font-extrabold text-slate-900 mb-1">هویت بصری و برندینگ</h3>
            <p class="text-xs text-slate-500 mb-5">نام سایت، لوگوی اصلی و فاوآیکون نمایش داده شده در مرورگر</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="admin-label">نام سایت</label>
                    <input type="text" wire:model.debounce.500ms="siteName" class="admin-input">
                    @error('siteName') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="admin-label">لوگوی اصلی سایت</label>
                    <input type="file" wire:model="siteLogo" accept="image/*" class="w-full text-xs text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
                    @error('siteLogo') <p class="admin-error">{{ $message }}</p> @enderror
                    @if ($siteLogo)
                        <div class="mt-3 inline-block rounded-xl border border-slate-200 bg-slate-50/50 p-2">
                            <img src="{{ $siteLogo->temporaryUrl() }}" alt="پیش‌نمایش لوگو" class="h-12 w-auto object-contain">
                        </div>
                    @elseif ($siteLogoPath)
                        <div class="mt-3 inline-block rounded-xl border border-slate-200 bg-slate-50/50 p-2">
                            <img src="{{ asset('storage/' . $siteLogoPath) }}" alt="لوگوی فعلی" class="h-12 w-auto object-contain">
                        </div>
                    @endif
                </div>

                <div>
                    <label class="admin-label">فاوآیکون (PNG، ICO یا SVG تا ۵۱۲ کیلوبایت)</label>
                    <input type="file" wire:model="siteFavicon" accept=".png,.ico,.svg,image/png,image/x-icon,image/svg+xml" class="w-full text-xs text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
                    @error('siteFavicon') <p class="admin-error">{{ $message }}</p> @enderror
                    @if ($siteFavicon)
                        @if ($siteFavicon->isPreviewable())
                            <div class="mt-3 inline-block rounded-xl border border-slate-200 bg-slate-50/50 p-2">
                                <img src="{{ $siteFavicon->temporaryUrl() }}" alt="پیش‌نمایش فاوآیکون" class="h-8 w-8 object-contain">
                            </div>
                        @endif
                    @elseif ($siteFaviconPath)
                        <div class="mt-3 flex items-center gap-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-2">
                                <img src="{{ asset('storage/' . $siteFaviconPath) }}" alt="فاوآیکون فعلی" class="h-8 w-8 object-contain">
                            </div>
                            <button type="button" wire:click="removeFavicon" class="text-xs text-rose-600 hover:text-rose-700 font-medium transition">حذف فاوآیکون</button>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Homepage banners --}}
        <section class="admin-card p-6">
            <h3 class="text-base font-extrabold text-slate-900 mb-1">بنرهای صفحه اصلی</h3>
            <p class="text-xs text-slate-500 mb-5">در صورت نبود تصویر، از پس‌زمینه رنگی استاندارد همراه با متن استفاده می‌شود.</p>

            <div class="space-y-4">
                @for ($i = 1; $i <= 3; $i++)
                    <div class="border border-slate-200/80 rounded-xl p-5 bg-slate-50/40">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold text-slate-800">اسلاید / بنر شماره {{ $i }}</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="admin-label">تصویر بنر</label>
                                <input type="file" wire:model="bannerImage{{ $i }}" accept="image/*" class="w-full text-xs text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
                                @error("bannerImage{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                                @if (${"bannerImage{$i}"})
                                    <img src="{{ ${"bannerImage{$i}"}->temporaryUrl() }}" alt="پیش‌نمایش بنر {{ $i }}" class="mt-3 h-28 w-full object-cover rounded-xl border border-slate-200">
                                @elseif (${"bannerImagePath{$i}"})
                                    <img src="{{ asset('storage/' . ${"bannerImagePath{$i}"}) }}" alt="بنر فعلی {{ $i }}" class="mt-3 h-28 w-full object-cover rounded-xl border border-slate-200">
                                @endif
                            </div>
                            <div>
                                <label class="admin-label">عنوان</label>
                                <input type="text" wire:model.debounce.300ms="bannerTitle{{ $i }}" class="admin-input">
                            </div>
                            <div>
                                <label class="admin-label">زیرعنوان</label>
                                <input type="text" wire:model.debounce.300ms="bannerSubtitle{{ $i }}" class="admin-input">
                            </div>
                            <div>
                                <label class="admin-label">متن دکمه (CTA)</label>
                                <input type="text" wire:model.debounce.300ms="bannerCtaText{{ $i }}" class="admin-input">
                            </div>
                            <div>
                                <label class="admin-label">لینک دکمه</label>
                                <input type="text" wire:model.debounce.300ms="bannerCtaUrl{{ $i }}" placeholder="https://example.com یا /pages/x" dir="ltr" class="admin-input">
                                @error("bannerCtaUrl{$i}") <p class="admin-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </section>

        {{-- Footer --}}
        <section class="admin-card p-6">
            <h3 class="text-base font-extrabold text-slate-900 mb-1">اطلاعات فوتر سایت</h3>
            <p class="text-xs text-slate-500 mb-5">متن درباره ما، آدرس و شماره‌های تماس در انتهای تمام صفحات</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="admin-label">متن درباره ما</label>
                    <textarea wire:model.debounce.300ms="footerAboutText" rows="3" class="admin-input"></textarea>
                    @error('footerAboutText') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">تلفن تماس پشتیبانی</label>
                    <input type="text" wire:model.debounce.300ms="contactPhone" dir="ltr" class="admin-input font-mono">
                    @error('contactPhone') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">ایمیل پشتیبانی</label>
                    <input type="email" wire:model.debounce.300ms="contactEmail" dir="ltr" class="admin-input font-mono">
                    @error('contactEmail') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="admin-label">آدرس فیزیکی دفتر</label>
                    <input type="text" wire:model.debounce.300ms="contactAddress" class="admin-input">
                    @error('contactAddress') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Icons --}}
        <section class="admin-card p-6">
            <h3 class="text-base font-extrabold text-slate-900 mb-1">تنظیمات آیکون‌های سیستمی</h3>
            <p class="text-xs text-slate-500 mb-5">نسخه و نحوه نمایش آیکون‌های کاربردی سایت را مدیریت کنید.</p>

            <div class="space-y-3">
                @foreach (config('icons.slots', []) as $key => $slot)
                    <div class="flex flex-wrap items-center gap-4 border border-slate-200/80 rounded-xl p-4 bg-slate-50/40">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-700 shadow-2xs">
                            <x-dynamic-component :component="'icons.' . str_replace('_', '-', $key)" :variant="$iconSettings[$key]['variant'] ?? null" class="h-5 w-5" />
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <p class="text-xs font-bold text-slate-900">{{ $slot['label'] }}</p>
                            @if ($slot['can_disable'])
                                <label class="mt-1 flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                                    <input type="checkbox" wire:model="iconSettings.{{ $key }}.enabled" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>نمایش آیکون</span>
                                </label>
                            @endif
                            @error("iconSettings.{$key}.variant") <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-center gap-3">
                            <select wire:model="iconSettings.{{ $key }}.variant" class="admin-select text-xs sm:w-44">
                                @foreach (config('icons.variants', []) as $variant => $meta)
                                    <option value="{{ $variant }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="resetIcon('{{ $key }}')" class="admin-btn admin-btn-secondary admin-btn-sm text-xs font-semibold">
                                بازنشانی
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('iconSettings') <p class="admin-error">{{ $message }}</p> @enderror
        </section>

        {{-- Header menu --}}
        <section class="admin-card p-6">
            <h3 class="text-base font-extrabold text-slate-900 mb-1">منوی اصلی بالای سایت (Header)</h3>
            <p class="text-xs text-slate-500 mb-5">ترتیب و نمایش آیتم‌های اصلی منوی هدر را مدیریت کنید.</p>

            <div class="space-y-3">
                @foreach ($menuItems as $id => $data)
                    <div class="flex flex-wrap items-center gap-3 border border-slate-200/80 rounded-xl p-3 bg-slate-50/40">
                        <div class="flex-1 min-w-[160px]">
                            <label class="sr-only">عنوان آیتم</label>
                            <input type="text" wire:model="menuItems.{{ $id }}.title" class="admin-input text-xs">
                            @error("menuItems.{$id}.title") <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="w-24">
                            <label class="sr-only">ترتیب</label>
                            <input type="number" min="0" wire:model="menuItems.{{ $id }}.sort_order" class="admin-input text-xs">
                        </div>
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" wire:model="menuItems.{{ $id }}.is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>نمایش</span>
                        </label>
                    </div>
                @endforeach
            </div>
            @error('menuItems') <p class="admin-error">{{ $message }}</p> @enderror
        </section>

        <div class="flex justify-end pt-2">
            <button type="submit" class="admin-btn admin-btn-primary font-semibold shadow-md shadow-indigo-600/20">
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>