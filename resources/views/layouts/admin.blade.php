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
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 start-0 z-40 w-64 transform bg-gray-900 text-white flex flex-col transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 lg:transform-none"
               :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'">
            <div class="p-4 border-b border-gray-800">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-lg font-bold">پنل ادمین</a>
            </div>
            @php
                $currentRoute = request()->route()?->getName() ?? '';
                $storeRoutes = ['admin.products', 'admin.colors', 'admin.designs', 'admin.designs.create', 'admin.designs.edit'];
                $contentRoutes = ['admin.pages', 'admin.articles', 'admin.article-categories', 'admin.faq', 'admin.announcements'];
                $appearanceRoutes = ['admin.appearance', 'admin.menus', 'admin.menu-items', 'admin.homepage-sections'];
                $groups = [
                    'store' => in_array($currentRoute, $storeRoutes),
                    'content' => in_array($currentRoute, $contentRoutes),
                    'appearance' => in_array($currentRoute, $appearanceRoutes),
                ];
            @endphp
            <nav class="flex-1 overflow-y-auto p-4 space-y-1"
                 x-data="{ open: @js($groups) }"
                 data-open-groups="{{ implode(' ', array_keys(array_filter($groups))) }}" />
                <a href="{{ route('admin.dashboard') }}" wire:navigate
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.dashboard') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    داشبورد
                </a>

                {{-- فروشگاه --}}
                <div>
                    <button type="button"
                            @click="open.store = !open.store"
                            :aria-expanded="open.store === true"
                            aria-controls="group-store"
                            aria-label="فروشگاه"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm transition {{ $groups['store'] ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <span>فروشگاه</span>
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="open.store ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="group-store" x-show="open.store" x-collapse.duration.200ms class="mt-1 ms-3 space-y-1 border-s-2 border-gray-800 ps-2">
                        <a href="{{ route('admin.products') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.products') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            محصولات
                        </a>
                        <a href="{{ route('admin.colors') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.colors') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            رنگ‌ها
                        </a>
                        <a href="{{ route('admin.designs') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.designs', 'admin.designs.create', 'admin.designs.edit') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            طرح‌ها
                        </a>
                    </div>
                </div>

                {{-- محتوا --}}
                <div>
                    <button type="button"
                            @click="open.content = !open.content"
                            :aria-expanded="open.content === true"
                            aria-controls="group-content"
                            aria-label="محتوا"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm transition {{ $groups['content'] ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <span>محتوا</span>
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="open.content ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="group-content" x-show="open.content" x-collapse.duration.200ms class="mt-1 ms-3 space-y-1 border-s-2 border-gray-800 ps-2">
                        <a href="{{ route('admin.pages') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.pages') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            صفحات
                        </a>
                        <a href="{{ route('admin.articles') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.articles') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            مقالات
                        </a>
                        <a href="{{ route('admin.article-categories') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.article-categories') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            دسته‌بندی مقالات
                        </a>
                        <a href="{{ route('admin.faq') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.faq') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            سوالات متداول
                        </a>
                        <a href="{{ route('admin.announcements') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.announcements') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            اطلاعیه‌ها
                        </a>
                    </div>
                </div>

                {{-- ظاهر سایت --}}
                <div>
                    <button type="button"
                            @click="open.appearance = !open.appearance"
                            :aria-expanded="open.appearance === true"
                            aria-controls="group-appearance"
                            aria-label="ظاهر سایت"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm transition {{ $groups['appearance'] ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <span>ظاهر سایت</span>
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="open.appearance ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="group-appearance" x-show="open.appearance" x-collapse.duration.200ms class="mt-1 ms-3 space-y-1 border-s-2 border-gray-800 ps-2">
                        <a href="{{ route('admin.appearance') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.appearance') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            ظاهر و برند
                        </a>
                        <a href="{{ route('admin.menus') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.menus') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            منوها
                        </a>
                        <a href="{{ route('admin.menu-items') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.menu-items') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            آیتم‌های منو
                        </a>
                        <a href="{{ route('admin.homepage-sections') }}" wire:navigate
                            class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.homepage-sections') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                            صفحه اصلی
                        </a>
                    </div>
                </div>

                <a href="{{ route('admin.orders') }}" wire:navigate
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.orders') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    سفارشات
                </a>
                <a href="{{ route('admin.payments') }}" wire:navigate
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.payments') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    پرداخت‌ها
                </a>
                <a href="{{ route('admin.manual-payment') }}" wire:navigate
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.manual-payment') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    تنظیمات پرداخت
                </a>
                <a href="{{ route('admin.users') }}" wire:navigate
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.users') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    کاربران
                </a>
                <a href="{{ route('admin.site-settings') }}" wire:navigate
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.site-settings') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    تنظیمات سایت
                </a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg text-sm hover:bg-gray-800 transition">بازگشت به سایت</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-start px-3 py-2 rounded-lg text-sm hover:bg-gray-800 transition">خروج</button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 p-4 overflow-auto sm:p-8">
            <button
                type="button"
                @click="sidebarOpen = !sidebarOpen"
                :aria-expanded="sidebarOpen"
                aria-label="باز کردن منو"
                class="lg:hidden inline-flex items-center p-2 mb-4 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
            >
                <x-icons.menu-toggle x-var="sidebarOpen" />
            </button>

            @if(session()->has('success'))
                <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
            @endif
            @if(session()->has('error'))
                <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 text-sm">{{ session('error') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>