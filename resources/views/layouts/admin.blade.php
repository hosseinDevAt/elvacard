<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل ادمین')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:300,400,500,600,700,800,900" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-gray-900 text-white flex flex-col">
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
                <a href="{{ route('admin.cate-designs') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.cate-designs') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    دسته‌بندی طرح‌ها
                </a>
                <a href="{{ route('admin.card-types') }}"
                    class="block px-3 py-2 rounded-lg text-sm transition {{ request()->routeIs('admin.card-types') ? 'bg-yellow-500' : 'hover:bg-gray-800' }}">
                    انواع کارت
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
                    <button type="submit" class="w-full text-right px-3 py-2 rounded-lg text-sm hover:bg-gray-800 transition">خروج</button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 p-8 overflow-auto">
            @if(session()->has('success'))
                <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
            @endif
            @if(session()->has('error'))
                <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 text-sm">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
    @livewireScripts
</body>
</html>
