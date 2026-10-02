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
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 selection:bg-[#ffde5b] selection:text-[#010619]">
    <div class="w-full max-w-md my-8">
        {{-- Brand Logo Header --}}
        <div class="text-center mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 group">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-md shadow-[#010619]/15 group-hover:scale-105 transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
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
                <x-icons.arrow-left class="h-3.5 w-3.5" />
                <span>بازگشت به صفحه اصلی</span>
            </a>
        </div>
    </div>

    <x-confirmation-modal />
    <x-toast-container />
    @livewireScripts
</body>
</html>
