<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">ظاهر سایت</h1>
    </div>

    @if(session()->has('success'))
        <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Identity --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-bold text-gray-900 mb-4">هویت برند</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">نام سایت</label>
                    <input type="text" wire:model.debounce.500ms="siteName" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('siteName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">لوگو</label>
                    <input type="file" wire:model="siteLogo" accept="image/*" class="w-full text-sm text-gray-600 file:me-3 file:rounded-lg file:border-0 file:bg-yellow-500 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-yellow-600">
                    @error('siteLogo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @if ($siteLogo)
                        <img src="{{ $siteLogo->temporaryUrl() }}" alt="پیش‌نمایش لوگو" class="mt-3 h-14 w-auto object-contain border border-gray-100 rounded-lg p-1">
                    @elseif ($siteLogoPath)
                        <img src="{{ asset('storage/' . $siteLogoPath) }}" alt="لوگوی فعلی" class="mt-3 h-14 w-auto object-contain border border-gray-100 rounded-lg p-1">
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">فاوآیکون (PNG، ICO یا SVG تا ۵۱۲ کیلوبایت)</label>
                    <input type="file" wire:model="siteFavicon" accept=".png,.ico,.svg,image/png,image/x-icon,image/svg+xml" class="w-full text-sm text-gray-600 file:me-3 file:rounded-lg file:border-0 file:bg-yellow-500 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-yellow-600">
                    @error('siteFavicon') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @if ($siteFavicon)
                        @if ($siteFavicon->isPreviewable())
                            <img src="{{ $siteFavicon->temporaryUrl() }}" alt="پیش‌نمایش فاوآیکون" class="mt-3 h-10 w-10 object-contain border border-gray-100 rounded p-1">
                        @endif
                    @elseif ($siteFaviconPath)
                        <img src="{{ asset('storage/' . $siteFaviconPath) }}" alt="فاوآیکون فعلی" class="mt-3 h-10 w-10 object-contain border border-gray-100 rounded p-1">
                        <button type="button" wire:click="removeFavicon" class="mt-2 text-xs text-red-600 hover:text-red-700">حذف فاوآیکون</button>
                    @endif
                </div>
            </div>
        </section>

        {{-- Homepage banners --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-bold text-gray-900 mb-1">بنرهای صفحه اصلی</h2>
            <p class="text-sm text-gray-500 mb-4">در صورت نبود تصویر، از پس‌زمینه رنگی با متن استفاده می‌شود.</p>

            @for ($i = 1; $i <= 3; $i++)
                <div class="mb-6 last:mb-0 border border-gray-100 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">بنر {{ $i }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">تصویر</label>
                            <input type="file" wire:model="bannerImage{{ $i }}" accept="image/*" class="w-full text-sm text-gray-600 file:me-3 file:rounded-lg file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-gray-700">
                            @error("bannerImage{$i}") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            @if (${"bannerImage{$i}"})
                                <img src="{{ ${"bannerImage{$i}"}->temporaryUrl() }}" alt="پیش‌نمایش بنر {{ $i }}" class="mt-3 h-28 w-full object-cover rounded-lg border border-gray-100">
                            @elseif (${"bannerImagePath{$i}"})
                                <img src="{{ asset('storage/' . ${"bannerImagePath{$i}"}) }}" alt="بنر فعلی {{ $i }}" class="mt-3 h-28 w-full object-cover rounded-lg border border-gray-100">
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">عنوان</label>
                            <input type="text" wire:model.debounce.300ms="bannerTitle{{ $i }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">زیرعنوان</label>
                            <input type="text" wire:model.debounce.300ms="bannerSubtitle{{ $i }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">متن دکمه</label>
                            <input type="text" wire:model.debounce.300ms="bannerCtaText{{ $i }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">لینک دکمه</label>
                            <input type="text" wire:model.debounce.300ms="bannerCtaUrl{{ $i }}" placeholder="https://example.com یا /pages/x" dir="ltr" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error("bannerCtaUrl{$i}") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endfor
        </section>

        {{-- Footer --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-bold text-gray-900 mb-4">فوتر سایت</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">متن درباره ما</label>
                    <textarea wire:model.debounce.300ms="footerAboutText" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                    @error('footerAboutText') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تلفن تماس</label>
                    <input type="text" wire:model.debounce.300ms="contactPhone" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('contactPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ایمیل</label>
                    <input type="email" wire:model.debounce.300ms="contactEmail" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('contactEmail') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">آدرس</label>
                    <input type="text" wire:model.debounce.300ms="contactAddress" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('contactAddress') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Icons --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-bold text-gray-900 mb-1">آیکون‌ها</h2>
            <p class="text-sm text-gray-500 mb-4">نسخه و نمایش آیکون‌های کاربردی سایت را مدیریت کنید. آیکون‌های جستجو و سبد خرید همیشه نمایش داده می‌شوند.</p>

            <div class="space-y-4">
                @foreach (config('icons.slots', []) as $key => $slot)
                    <div class="flex flex-wrap items-center gap-4 border border-gray-100 rounded-xl p-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-50 text-gray-700">
                            <x-dynamic-component :component="'icons.' . str_replace('_', '-', $key)" :variant="$iconSettings[$key]['variant'] ?? null" class="h-6 w-6" />
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <p class="text-sm font-semibold text-gray-900">{{ $slot['label'] }}</p>
                            @if ($slot['can_disable'])
                                <label class="mt-1 flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" wire:model="iconSettings.{{ $key }}.enabled" class="rounded border-gray-300 text-yellow-500">
                                    نمایش آیکون
                                </label>
                            @endif
                            @error("iconSettings.{$key}.variant") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-center gap-3">
                            <select wire:model="iconSettings.{{ $key }}.variant"
                                    class="px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                                @foreach (config('icons.variants', []) as $variant => $meta)
                                    <option value="{{ $variant }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="resetIcon('{{ $key }}')"
                                    class="text-xs text-red-600 hover:text-red-700">
                                بازنشانی
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('iconSettings') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </section>

        {{-- Header menu --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-bold text-gray-900 mb-1">منوی هدر</h2>
            <p class="text-sm text-gray-500 mb-4">ترتیب و نمایش آیتم‌های اصلی منو را مدیریت کنید.</p>

            <div class="space-y-3">
                @foreach ($menuItems as $id => $data)
                    <div class="flex flex-wrap items-center gap-3 border border-gray-100 rounded-lg p-3">
                        <div class="flex-1 min-w-[160px]">
                            <label class="sr-only">عنوان آیتم</label>
                            <input type="text" wire:model="menuItems.{{ $id }}.title" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                            @error("menuItems.{$id}.title") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="w-24">
                            <label class="sr-only">ترتیب</label>
                            <input type="number" min="0" wire:model="menuItems.{{ $id }}.sort_order" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="menuItems.{{ $id }}.is_active" class="rounded border-gray-300 text-yellow-500">
                            نمایش
                        </label>
                    </div>
                @endforeach
            </div>
            @error('menuItems') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </section>

        <div class="flex justify-end">
            <button type="submit" class="bg-yellow-500 text-white px-8 py-2.5 rounded-lg text-sm font-medium hover:bg-yellow-600 transition">
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>