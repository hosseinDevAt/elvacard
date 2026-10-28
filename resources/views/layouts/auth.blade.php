<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon-links')
    <title>@yield('title', 'ورود - الواکارت')</title>
    <link rel="preload" href="/fonts/Yekan_Bakh_Fanum_Regular.TTF" as="font" type="font/ttf" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 selection:bg-[#ffde5b] selection:text-[#010619]">
    <div class="w-full max-w-md my-8">
        {{-- Brand Logo Header --}}
        <div class="text-center mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 group">
                <span class="flex h-12 w-12 items-center justify-center rounded-full shadow-lg shadow-slate-300/60 group-hover:scale-105 transition">
                    <x-application-logo variant="dark" class="h-12 w-12" />
                </span>
                <span class="text-2xl font-black text-[#010619] tracking-tight">{{ site_setting('site_name', 'الواکارت') }}</span>
            </a>
        </div>

        {{-- Main Form Card --}}
        <div class="rounded-3xl border border-slate-200/90 bg-white shadow-xl shadow-slate-200/40 p-6 sm:p-8">
            @yield('content')
        </div>

        {{-- Footer Link --}}
        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-[#010619] transition">
                <x-icons.arrow-right class="h-3.5 w-3.5" />
                <span>بازگشت به صفحه اصلی</span>
            </a>
        </div>
    </div>

    <x-confirmation-modal />
    <x-toast-container />
    @livewireScripts
</body>
</html>
