<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Single authoritative document title for this layout. Content views
             override it with @section('title'); every other public page falls
             back to the site name. Content views must NOT emit their own title
             tag here, or the page would carry two of them. --}}
        <title>@yield('title', site_setting('site_name', config('app.name')))</title>

        @include('components.favicon-links')

        @yield('meta')


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="font-sans antialiased text-slate-800 selection:bg-[#ffde5b] selection:text-[#010619]">
        <div class="min-h-screen bg-[#f8fafc] flex flex-col">
            @include('layouts.announcement')
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1">
                @yield('content')
                {{ $slot ?? '' }}
            </main>

            @include('layouts.footer')
        </div>

        <x-confirmation-modal />
        <x-toast-container />
        @livewireScripts
    </body>
</html>
