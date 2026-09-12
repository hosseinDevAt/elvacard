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
                <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold">پنل ادمین</a>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.dashboard') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    داشبورد
                </a>
                <a href="{{ route('admin.colors') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.colors') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    رنگ‌ها
                </a>
                <a href="{{ route('admin.products') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.products') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    محصولات
                </a>
                <a href="{{ route('admin.product-colors') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.product-colors') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    قیمت رنگ محصولات
                </a>
                <a href="{{ route('admin.cate-designs') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.cate-designs') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    دسته‌بندی طرح‌ها
                </a>
                <a href="{{ route('admin.designs') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.designs') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    طرح‌ها
                </a>
                <a href="{{ route('admin.design-images') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.design-images') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    تصاویر طرح‌ها
                </a>
                <a href="{{ route('admin.design-color-compatibilities') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.design-color-compatibilities') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    سازگاری رنگ طرح‌ها
                </a>
                <a href="{{ route('admin.faq') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.faq') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    مدیریت سوالات متداول
                </a>
                <a href="{{ route('admin.announcements') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.announcements') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    مدیریت اطلاعیه‌ها
                </a>
                <a href="{{ route('admin.article-categories') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.article-categories') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    دسته‌بندی مقالات
                </a>
                <a href="{{ route('admin.articles') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.articles') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    مقالات
                </a>
                <a href="{{ route('admin.pages') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.pages') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    مدیریت صفحات
                </a>
                <a href="{{ route('admin.menus') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.menus') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    مدیریت منوها
                </a>
                <a href="{{ route('admin.menu-items') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.menu-items') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    آیتم‌های منو
                </a>
                <a href="{{ route('admin.homepage-sections') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.homepage-sections') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    مدیریت صفحه اصلی
                </a>
                <a href="{{ route('admin.site-settings') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.site-settings') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    تنظیمات سایت
                </a>
                <a href="{{ route('admin.appearance') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.appearance') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    ظاهر سایت
                </a>
                <a href="{{ route('admin.orders') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.orders') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    سفارشات
                </a>
                <a href="{{ route('admin.users') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.users') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    کاربران
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