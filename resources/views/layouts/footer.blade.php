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
<footer class="bg-gray-900 border-t border-gray-800 text-gray-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Brand -->
            <div class="lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2 mb-4">
                    @if ($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain">
                    @else
                        <span class="flex items-center justify-center h-10 w-10 rounded-xl bg-primary-600 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h2m4 0h4m-9 5h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                    @endif
                    <span class="text-lg font-bold text-white">{{ $siteName }}</span>
                </a>

                @if ($footerAbout)
                    <p class="text-sm leading-relaxed text-gray-400 max-w-md">
                        {{ $footerAbout }}
                    </p>
                @endif
            </div>

            <!-- Useful Links -->
            <div>
                <h3 class="text-sm font-bold text-white mb-4">لینک‌های مفید</h3>
                <ul class="space-y-2">
                    @forelse ($footerItems as $item)
                        <li>
                            @if ($item->target === '_blank')
                                <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer"
                                   class="text-sm text-gray-400 hover:text-white transition">
                                    {{ $item->title }}
                                </a>
                            @else
                                <a href="{{ $item->url }}" class="text-sm text-gray-400 hover:text-white transition">
                                    {{ $item->title }}
                                </a>
                            @endif
                        </li>
                    @empty
                        <li>
                            <a href="{{ route('catalog.products.index') }}" class="text-sm text-gray-400 hover:text-white transition">
                                فروشگاه
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('catalog.designs.index') }}" class="text-sm text-gray-400 hover:text-white transition">
                                طرح‌ها
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('articles.index') }}" class="text-sm text-gray-400 hover:text-white transition">
                                مقالات
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('faq.index') }}" class="text-sm text-gray-400 hover:text-white transition">
                                سوالات متداول
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h3 class="text-sm font-bold text-white mb-4">تماس با ما</h3>
                <ul class="space-y-3 text-sm text-gray-400">
                    @if ($contactPhone)
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <a href="tel:{{ $contactPhone }}" dir="ltr" class="hover:text-white transition">{{ $contactPhone }}</a>
                        </li>
                    @endif
                    @if ($contactEmail)
                        <li class="flex items-center gap-2 break-all">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <a href="mailto:{{ $contactEmail }}" class="hover:text-white transition">{{ $contactEmail }}</a>
                        </li>
                    @endif
                    @if ($contactAddress)
                        <li class="flex items-start gap-2 break-words">
                            <svg class="h-4 w-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ $contactAddress }}</span>
                        </li>
                    @endif
                    @if (! $contactPhone && ! $contactEmail && ! $contactAddress)
                        <li>
                            <a href="{{ route('pages.show', 'contact-us') }}" class="hover:text-white transition">
                                صفحه تماس با ما
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-gray-800 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-gray-500">
            <div class="text-center md:text-start">
                © <span>{{ date('Y') }}</span>
                {{ $siteName }}. تمامی حقوق محفوظ است.
            </div>
            <div class="text-center">
                الواکارت؛ ساخت کارت‌های شخصی و اختصاصی.
            </div>
        </div>
    </div>
</footer>