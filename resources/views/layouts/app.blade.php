<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'کارت شخصی'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:300,400,500,600,700,800,900" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex flex-col">
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14 sm:h-16">
                <a href="{{ route('home') }}" class="text-lg sm:text-xl font-bold text-yellow-500">
                    کارت شخصی
                </a>
                <nav class="flex items-center gap-2 sm:gap-4">
                    <a href="{{ route('home') }}" class="text-xs sm:text-sm text-gray-600 hover:text-gray-900">خانه</a>
                    <a href="{{ route('designer.bank') }}" class="text-xs sm:text-sm text-gray-600 hover:text-gray-900">طراحی کارت</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-xs sm:text-sm text-gray-600 hover:text-gray-900">پنل کاربری</a>
                    @else
                        <a href="{{ route('login') }}" class="text-xs sm:text-sm bg-yellow-500 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:bg-yellow-600 transition">ورود</a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs sm:text-sm text-gray-500">
            © {{ date('Y') }} کارت شخصی. تمامی حقوق محفوظ است.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
