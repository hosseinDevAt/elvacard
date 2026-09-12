<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon-links')
    <title>@yield('title', 'ورود - الواکارت')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:300,400,500,600,700,800,900" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="text-2xl font-bold text-primary-600">{{ site_setting('site_name', 'الواکارت') }}</a>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            @yield('content')
        </div>
        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-700">بازگشت به خانه</a>
        </div>
    </div>
    @livewireScripts
</body>
</html>
