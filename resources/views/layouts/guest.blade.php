<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Single authoritative document title, matching layouts.app. A guest
             page overrides it with @section('title'); guest pages without SEO
             metadata keep the site name fallback. --}}
        <title>@yield('title', site_setting('site_name', config('app.name')))</title>

        @include('components.favicon-links')

        @yield('meta')

        {{-- Preload primary font to eliminate FOIT/FOUT and improve FCP --}}
        <link rel="preload" href="/fonts/Yekan_Bakh_Fanum_Regular.TTF" as="font" type="font/ttf" crossorigin>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-8 sm:pt-0 bg-[#f8fafc] px-4">
            <div>
                <a href="/" wire:navigate class="flex flex-col items-center gap-3 group">
                    <span class="inline-flex h-16 w-16 items-center justify-center rounded-full shadow-xl shadow-slate-300/60 transition-transform group-hover:scale-105">
                        <x-application-logo variant="dark" class="h-16 w-16" />
                    </span>
                    <span class="text-xl font-extrabold text-[#010619] tracking-tight">{{ site_setting('site_name', config('app.name')) }}</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-xl shadow-slate-200/50 border border-slate-200/80 overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
        <x-confirmation-modal />
        <x-toast-container />
        @livewireScripts
    </body>
</html>
