<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon-links')
    <title>{{ $title ?? 'پنل ادمین' }}</title>
    <link rel="preload" href="/fonts/Yekan_Bakh_Fanum_Regular.TTF" as="font" type="font/ttf" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f8fafc] text-slate-800 antialiased font-sans">
    <div class="flex min-h-screen" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

        @php
            $currentRoute = request()->route()?->getName() ?? '';
            $sidebar = [
                [
                    'id' => 'store',
                    'label' => 'فروشگاه',
                    'icon' => 'box',
                    'routes' => ['admin.products', 'admin.product-categories'],
                    'items' => [
                        ['route' => 'admin.products', 'label' => 'محصولات', 'icon' => 'box', 'routes' => ['admin.products']],
                        ['route' => 'admin.product-categories', 'label' => 'دسته‌بندی محصولات', 'icon' => 'tags', 'routes' => ['admin.product-categories']],
                    ],
                ],
                [
                    'id' => 'custom-design',
                    'label' => 'شخصی‌سازی کارت',
                    'icon' => 'palette',
                    'routes' => ['admin.product-colors', 'admin.designs', 'admin.designs.create', 'admin.designs.edit', 'admin.design-images', 'admin.design-color-compatibilities'],
                    'items' => [
                        ['route' => 'admin.product-colors', 'label' => 'قیمت‌گذاری کارت‌ها', 'icon' => 'credit-card', 'routes' => ['admin.product-colors']],
                        ['route' => 'admin.designs', 'label' => 'طرح‌ها', 'icon' => 'palette', 'routes' => ['admin.designs', 'admin.designs.create', 'admin.designs.edit']],
                        ['route' => 'admin.design-images', 'label' => 'تصاویر طرح‌ها', 'icon' => 'image', 'routes' => ['admin.design-images']],
                        ['route' => 'admin.design-color-compatibilities', 'label' => 'سازگاری رنگ طرح‌ها', 'icon' => 'check-badge', 'routes' => ['admin.design-color-compatibilities']],
                    ],
                ],
                [
                    'id' => 'basic-data',
                    'label' => 'اطلاعات پایه',
                    'icon' => 'database',
                    'routes' => ['admin.colors'],
                    'items' => [
                        ['route' => 'admin.colors', 'label' => 'رنگ‌ها', 'icon' => 'droplet', 'routes' => ['admin.colors']],
                    ],
                ],
                [
                    'id' => 'payments',
                    'label' => 'پرداخت',
                    'icon' => 'wallet',
                    'routes' => ['admin.payments', 'admin.manual-payment'],
                    'items' => [
                        ['route' => 'admin.payments', 'label' => 'پرداخت‌ها', 'icon' => 'wallet', 'routes' => ['admin.payments']],
                        ['route' => 'admin.manual-payment', 'label' => 'پرداخت‌های دستی', 'icon' => 'banknote', 'routes' => ['admin.manual-payment']],
                    ],
                ],
                [
                    'id' => 'content',
                    'label' => 'محتوا',
                    'icon' => 'document-text',
                    'routes' => ['admin.pages', 'admin.homepage-sections', 'admin.articles', 'admin.article-categories', 'admin.faq', 'admin.order-process', 'admin.announcements', 'admin.menus', 'admin.menu-items'],
                    'items' => [
                        ['route' => 'admin.pages', 'label' => 'صفحات', 'icon' => 'document-text', 'routes' => ['admin.pages']],
                        ['route' => 'admin.homepage-sections', 'label' => 'صفحه اصلی', 'icon' => 'home', 'routes' => ['admin.homepage-sections']],
                        ['route' => 'admin.articles', 'label' => 'مقالات', 'icon' => 'newspaper', 'routes' => ['admin.articles']],
                        ['route' => 'admin.article-categories', 'label' => 'دسته‌بندی مقالات', 'icon' => 'folder', 'routes' => ['admin.article-categories']],
                        ['route' => 'admin.faq', 'label' => 'سوالات متداول', 'icon' => 'lifebuoy', 'routes' => ['admin.faq']],
                        ['route' => 'admin.order-process', 'label' => 'مراحل سفارش', 'icon' => 'check-badge', 'routes' => ['admin.order-process']],
                        ['route' => 'admin.announcements', 'label' => 'اطلاعیه‌ها', 'icon' => 'megaphone', 'routes' => ['admin.announcements']],
                        ['route' => 'admin.menus', 'label' => 'منوها', 'icon' => 'list', 'routes' => ['admin.menus']],
                        ['route' => 'admin.menu-items', 'label' => 'آیتم‌های منو', 'icon' => 'list', 'routes' => ['admin.menu-items']],
                    ],
                ],
                [
                    'id' => 'appearance',
                    'label' => 'تنظیمات و ظاهر',
                    'icon' => 'cog',
                    'routes' => ['admin.site-settings', 'admin.appearance', 'admin.banners'],
                    'items' => [
                        ['route' => 'admin.site-settings', 'label' => 'تنظیمات سایت', 'icon' => 'cog', 'routes' => ['admin.site-settings']],
                        ['route' => 'admin.appearance', 'label' => 'ظاهر و برند', 'icon' => 'sparkles', 'routes' => ['admin.appearance']],
                        ['route' => 'admin.banners', 'label' => 'مدیریت بنرها', 'icon' => 'image', 'routes' => ['admin.banners']],
                    ],
                ],
            ];
            $openGroups = [];
            $groups = [];
            foreach ($sidebar as $section) {
                $sectionActive = in_array($currentRoute, $section['routes']);
                $groups[$section['id']] = $sectionActive;
                if ($sectionActive) {
                    $openGroups[] = $section['id'];
                }
            }
            $flatLabels = [
                'admin.dashboard' => 'داشبورد',
                'admin.orders' => 'سفارشات',
                'admin.users' => 'کاربران',
                'admin.reports' => 'گزارش‌ها',
            ];
            $sectionLabel = $flatLabels[$currentRoute] ?? null;
            if ($sectionLabel === null) {
                foreach ($sidebar as $section) {
                    if (in_array($currentRoute, $section['routes'])) {
                        $sectionLabel = $section['label'];
                        break;
                    }
                }
            }
        @endphp

        @include('layouts.partials.admin-sidebar', [
            'sidebar' => $sidebar,
            'openGroups' => $openGroups,
            'groups' => $groups,
            'currentRoute' => $currentRoute,
        ])

        <div class="flex min-w-0 flex-1 flex-col">
            @include('layouts.partials.admin-header', ['sectionLabel' => $sectionLabel])

            @if(app(\App\Services\MaintenanceModeManager::class)->isActive())
                <aside aria-label="هشدار حالت تعمیر" class="bg-amber-400 text-slate-950 px-4 py-2 text-xs font-bold flex flex-wrap items-center justify-between gap-2 shadow-sm border-b border-amber-500/30">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>حالت تعمیر و نگهداری فعال است: دسترسی کاربران عادی به صفحات عمومی سایت مسدود است.</span>
                    </div>
                    <a href="{{ route('admin.site-settings') }}" class="underline hover:text-black font-extrabold text-[11px]">
                        مدیریت وضعیت سایت &larr;
                    </a>
                </aside>
            @endif

            <main class="flex-1 p-4 sm:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
    <x-confirmation-modal />
    <x-toast-container />
    @livewireScripts
</body>
</html>