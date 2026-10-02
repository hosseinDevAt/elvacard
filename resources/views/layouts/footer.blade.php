<?php

use App\Models\Menu;

$footerMenu = Menu::query()
    ->where('location', 'footer')
    ->with([
        'items' => fn ($query) => $query
            ->active()
            ->orderBy('sort_order'),
    ])
    ->first();

$footerItems = collect();

if ($footerMenu && $footerMenu->items && $footerMenu->items->isNotEmpty()) {
    foreach ($footerMenu->items as $menuItem) {
        $url = $menuItem->resolveUrl();

        if ($url !== null) {
            $footerItems->push((object) [
                'title' => $menuItem->title,
                'url' => $url,
                'target' => $menuItem->target ?? '_self',
            ]);
        }
    }
}

$siteName = site_setting('site_name', config('app.name'));
$siteLogo = site_setting('site_logo');
$footerAbout = site_setting('footer_about_text');
$contactPhone = site_setting('contact_phone');
$contactEmail = site_setting('contact_email');
$contactAddress = site_setting('contact_address');
?>
<footer class="bg-[#010619] border-t border-[#152244] text-slate-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Brand -->
            <div class="lg:col-span-2 space-y-4">
                <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-3 group">
                    @if ($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain">
                    @else
                        <span class="flex items-center justify-center h-10 w-10 rounded-xl bg-[#ffde5b] text-[#010619] shadow-md shadow-[#ffde5b]/25 group-hover:scale-105 transition-transform duration-200">
                            <x-application-logo class="h-7 w-7 text-[#010619]" />
                        </span>
                    @endif
                    <span class="text-xl font-extrabold text-white tracking-tight">{{ $siteName }}</span>
                </a>

                @if ($footerAbout)
                    <p class="text-sm leading-relaxed text-slate-400 max-w-md">
                        {{ $footerAbout }}
                    </p>
                @endif
            </div>

            <!-- Useful Links -->
            <div>
                <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#ffde5b]"></span>
                    <span>لینک‌های مفید</span>
                </h3>
                <ul class="space-y-2.5">
                    @forelse ($footerItems as $item)
                        <li>
                            @if ($item->target === '_blank')
                                <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer"
                                   class="text-sm text-slate-400 hover:text-[#ffde5b] transition-colors duration-150 inline-block">
                                    {{ $item->title }}
                                </a>
                            @else
                                <a href="{{ $item->url }}" wire:navigate class="text-sm text-slate-400 hover:text-[#ffde5b] transition-colors duration-150 inline-block">
                                    {{ $item->title }}
                                </a>
                            @endif
                        </li>
                    @empty
                        <li>
                            <a href="{{ route('catalog.products.index') }}" wire:navigate class="text-sm text-slate-400 hover:text-[#ffde5b] transition-colors duration-150 inline-block">
                                فروشگاه
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('catalog.designs.index') }}" wire:navigate class="text-sm text-slate-400 hover:text-[#ffde5b] transition-colors duration-150 inline-block">
                                طرح‌ها
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('articles.index') }}" wire:navigate class="text-sm text-slate-400 hover:text-[#ffde5b] transition-colors duration-150 inline-block">
                                مقالات
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('faq.index') }}" wire:navigate class="text-sm text-slate-400 hover:text-[#ffde5b] transition-colors duration-150 inline-block">
                                سوالات متداول
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#ffde5b]"></span>
                    <span>تماس با ما</span>
                </h3>
                <ul class="space-y-3 text-sm text-slate-400">
                    @if ($contactPhone && site_icon_enabled('phone'))
                        <li class="flex items-center gap-2.5">
                            <span class="p-1 rounded-md bg-white/5 text-[#ffde5b]">
                                <x-icons.phone class="h-4 w-4 shrink-0" />
                            </span>
                            <a href="tel:{{ $contactPhone }}" dir="ltr" class="hover:text-white transition">{{ $contactPhone }}</a>
                        </li>
                    @endif
                    @if ($contactEmail && site_icon_enabled('mail'))
                        <li class="flex items-center gap-2.5 break-all">
                            <span class="p-1 rounded-md bg-white/5 text-[#ffde5b]">
                                <x-icons.mail class="h-4 w-4 shrink-0" />
                            </span>
                            <a href="mailto:{{ $contactEmail }}" class="hover:text-white transition">{{ $contactEmail }}</a>
                        </li>
                    @endif
                    @if ($contactAddress && site_icon_enabled('map_pin'))
                        <li class="flex items-start gap-2.5 break-words">
                            <span class="p-1 rounded-md bg-white/5 text-[#ffde5b] mt-0.5">
                                <x-icons.map-pin class="h-4 w-4 shrink-0" />
                            </span>
                            <span>{{ $contactAddress }}</span>
                        </li>
                    @endif
                    @if (! $contactPhone && ! $contactEmail && ! $contactAddress)
                        <li>
                            <a href="{{ route('pages.show', 'contact-us') }}" wire:navigate class="hover:text-white transition">
                                صفحه تماس با ما
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-6 border-t border-[#152244] flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-400">
            <div class="text-center md:text-start">
                © <span>{{ jalali_now('year') }}</span>
                {{ $siteName }}. تمامی حقوق محفوظ است.
            </div>
            <div class="text-center">
                الواکارت؛ طراحی و ساخت کارت‌های شخصی و سازمانی.
            </div>
        </div>
    </div>
</footer>