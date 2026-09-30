<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon-links')
    <title>{{ $title ?? 'پنل ادمین' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:300,400,500,600,700,800,900" rel="stylesheet" />
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
                    'routes' => ['admin.pages', 'admin.homepage-sections', 'admin.articles', 'admin.article-categories', 'admin.faq', 'admin.announcements', 'admin.menus', 'admin.menu-items'],
                    'items' => [
                        ['route' => 'admin.pages', 'label' => 'صفحات', 'icon' => 'document-text', 'routes' => ['admin.pages']],
                        ['route' => 'admin.homepage-sections', 'label' => 'صفحه اصلی', 'icon' => 'home', 'routes' => ['admin.homepage-sections']],
                        ['route' => 'admin.articles', 'label' => 'مقالات', 'icon' => 'newspaper', 'routes' => ['admin.articles']],
                        ['route' => 'admin.article-categories', 'label' => 'دسته‌بندی مقالات', 'icon' => 'folder', 'routes' => ['admin.article-categories']],
                        ['route' => 'admin.faq', 'label' => 'سوالات متداول', 'icon' => 'lifebuoy', 'routes' => ['admin.faq']],
                        ['route' => 'admin.announcements', 'label' => 'اطلاعیه‌ها', 'icon' => 'megaphone', 'routes' => ['admin.announcements']],
                        ['route' => 'admin.menus', 'label' => 'منوها', 'icon' => 'list', 'routes' => ['admin.menus']],
                        ['route' => 'admin.menu-items', 'label' => 'آیتم‌های منو', 'icon' => 'list', 'routes' => ['admin.menu-items']],
                    ],
                ],
                [
                    'id' => 'appearance',
                    'label' => 'تنظیمات و ظاهر',
                    'icon' => 'cog',
                    'routes' => ['admin.site-settings', 'admin.appearance'],
                    'items' => [
                        ['route' => 'admin.site-settings', 'label' => 'تنظیمات سایت', 'icon' => 'cog', 'routes' => ['admin.site-settings']],
                        ['route' => 'admin.appearance', 'label' => 'ظاهر و برند', 'icon' => 'sparkles', 'routes' => ['admin.appearance']],
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

            <main class="flex-1 p-4 sm:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>