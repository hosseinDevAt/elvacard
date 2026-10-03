<?php

use App\Models\Menu;

$headerMenu = Menu::query()
    ->where('location', 'header')
    ->with([
        'items' => fn ($query) => $query
            ->active()
            ->orderBy('sort_order'),
    ])
    ->first();

$navItems = collect();

if ($headerMenu && $headerMenu->items && $headerMenu->items->isNotEmpty()) {
    foreach ($headerMenu->items as $menuItem) {
        $url = $menuItem->resolveUrl();

        if ($url !== null) {
            $parsedPath = parse_url($url, PHP_URL_PATH) ?: '/';
            $currentPath = request()->getPathInfo();
            $isActive = ($parsedPath === '/' && $currentPath === '/')
                || ($parsedPath !== '/' && ($currentPath === $parsedPath || str_starts_with($currentPath, $parsedPath . '/')));

            $link = [
                'title' => $menuItem->title,
                'url' => $url,
                'target' => $menuItem->target ?? '_self',
                'active' => $isActive,
            ];

            $navItems->push((object) $link);
        }
    }
}

$siteName = site_setting('site_name', config('app.name'));
$siteLogo = site_setting('site_logo');
$cartCount = (int) (app(\App\Services\CartService::class)->getCart()['total_quantity'] ?? 0);
?>
<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-[#010619]/95 backdrop-blur-xl border-b border-[#152244] shadow-lg shadow-black/30">
    <!-- Primary Navigation Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-18 sm:h-20">
            <!-- Brand Logo + Mobile Hamburger -->
            <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open"
                    aria-label="تغییر وضعیت منو"
                    class="inline-flex items-center justify-center p-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/10 focus:outline-none transition-all duration-150 lg:hidden"
                >
                    <x-icons.menu-toggle x-var="open" />
                </button>

                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 group py-1">
                    @if ($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-10 sm:h-11 w-auto object-contain">
                    @else
                        <span class="flex items-center justify-center h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-[#ffde5b] text-[#010619] shadow-md shadow-[#ffde5b]/30 group-hover:scale-105 group-hover:rotate-1 transition-all duration-200">
                            <x-application-logo class="h-6 w-6 sm:h-7 sm:w-7 text-[#010619]" />
                        </span>
                    @endif
                    <div class="flex flex-col">
                        <span class="text-lg sm:text-xl font-black text-white tracking-tight group-hover:text-[#ffde5b] transition-colors whitespace-nowrap">{{ $siteName }}</span>
                        <span class="hidden xl:block text-[10px] font-semibold text-slate-400 tracking-wider">کارت شخصی و هوشمند فلزی</span>
                    </div>
                </a>
            </div>

            <!-- Desktop Navigation Menu (Center / Spaced) -->
            <div class="hidden lg:flex items-center gap-1.5 xl:gap-2">
                @foreach ($navItems as $link)
                    @if ($link->target === '_blank')
                        <x-nav-link :href="$link->url" :active="$link->active" target="{{ $link->target }}" rel="noopener noreferrer">
                            {{ $link->title }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="$link->url" :active="$link->active" wire:navigate>
                            {{ $link->title }}
                        </x-nav-link>
                    @endif
                @endforeach
            </div>

            <!-- Header Action Cluster (Left Side in RTL) -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Compact Search -->
                <form method="GET" action="{{ route('catalog.products.index') }}" class="hidden md:block">
                    <label class="relative block group">
                        <span class="sr-only">جستجو در فروشگاه</span>
                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="جستجو در محصولات..."
                            class="w-40 lg:w-48 xl:w-56 focus:w-64 rounded-xl border border-white/10 bg-white/[0.05] hover:bg-white/[0.08] focus:bg-[#010619] py-2 ps-3.5 pe-9 text-xs sm:text-sm text-white placeholder-slate-400 transition-all duration-200 focus:border-[#ffde5b] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/25"
                        >
                        <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 group-focus-within:text-[#ffde5b] transition-colors">
                            <x-icons.search class="h-4 w-4" />
                        </span>
                    </label>
                </form>

                <!-- Order Tracking / Account Area -->
                @if (Auth::check() && Auth::user()->role === 'admin')
                    <div class="hidden sm:block">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center gap-2 px-3.5 py-2 border border-white/10 text-xs sm:text-sm font-bold rounded-xl text-white bg-white/5 hover:bg-white/10 focus:outline-none transition">
                                    <span>{{ Auth::user()->displayName() }}</span>
                                    <x-icons.dropdown-chevron />
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                @if (Route::has('admin.dashboard'))
                                    <x-dropdown-link :href="route('admin.dashboard')" wire:navigate>
                                        پنل مدیریت
                                    </x-dropdown-link>
                                @endif

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        خروج
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @else
                    <a href="{{ route('order-tracking.index') }}" wire:navigate class="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-white/15 bg-white/5 hover:border-[#ffde5b] hover:bg-[#ffde5b] hover:text-[#010619] px-3.5 py-2 text-xs font-bold text-slate-200 transition-all duration-200 shadow-sm" title="پیگیری سفارش">
                        <x-icons.search class="h-3.5 w-3.5" />
                        <span>پیگیری سفارش</span>
                    </a>
                @endif

                <!-- Premium Cart Button -->
                <a href="{{ route('cart.index') }}" wire:navigate class="relative inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/[0.08] hover:bg-[#ffde5b] hover:border-[#ffde5b] hover:text-[#010619] text-white px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-bold transition-all duration-200 shadow-sm group" title="سبد خرید">
                    <x-icons.cart class="h-5 w-5 group-hover:scale-110 transition-transform" />
                    <span class="hidden lg:inline">سبد خرید</span>
                    @if ($cartCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#ffde5b] group-hover:bg-[#010619] text-[#010619] group-hover:text-[#ffde5b] px-1 text-[11px] font-black leading-none shadow-sm shadow-[#ffde5b]/40">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="border-t border-[#152244] bg-[#010619]/98 backdrop-blur-2xl lg:hidden shadow-2xl"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 space-y-4">
            <!-- Mobile Search Field -->
            <form method="GET" action="{{ route('catalog.products.index') }}">
                <label class="relative block">
                    <span class="sr-only">جستجو در فروشگاه</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="جستجو در محصولات..."
                        class="w-full rounded-xl border border-slate-700/80 bg-slate-900/80 py-2.5 ps-4 pe-10 text-sm text-white placeholder-slate-400 focus:border-[#ffde5b] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/30"
                    >
                    <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400">
                        <x-icons.search class="h-4 w-4" />
                    </span>
                </label>
            </form>

            <!-- Navigation Links -->
            @if ($navItems->isNotEmpty())
                <div class="space-y-1">
                    @foreach ($navItems as $link)
                        @if ($link->target === '_blank')
                            <x-responsive-nav-link
                                :href="$link->url"
                                :active="$link->active"
                                target="{{ $link->target }}"
                                rel="noopener noreferrer"
                            >
                                {{ $link->title }}
                            </x-responsive-nav-link>
                        @else
                            <x-responsive-nav-link
                                :href="$link->url"
                                :active="$link->active"
                                target="{{ $link->target }}"
                                wire:navigate
                            >
                                {{ $link->title }}
                            </x-responsive-nav-link>
                        @endif
                    @endforeach
                </div>
            @endif

            <!-- Mobile Quick Actions -->
            <div class="pt-3 border-t border-[#152244] space-y-2">
                <x-responsive-nav-link :href="route('cart.index')" wire:navigate>
                    <span class="flex items-center justify-between w-full">
                        <span class="inline-flex items-center gap-2 font-bold">
                            <x-icons.cart class="w-4 h-4 text-[#ffde5b]" />
                            <span>سبد خرید</span>
                        </span>
                        @if ($cartCount > 0)
                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#ffde5b] text-[#010619] px-1.5 text-xs font-black shadow-sm">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('order-tracking.index')" wire:navigate>
                    <span class="inline-flex items-center gap-2">
                        <x-icons.search class="w-4 h-4 text-[#ffde5b]" />
                        <span>پیگیری سفارش</span>
                    </span>
                </x-responsive-nav-link>

                @if (Auth::check() && Auth::user()->role === 'admin')
                    @if (Route::has('admin.dashboard'))
                        <x-responsive-nav-link :href="route('admin.dashboard')" wire:navigate>
                            پنل مدیریت
                        </x-responsive-nav-link>
                    @endif
                    <div class="pt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full ps-3 pe-4 py-2 border-s-4 border-transparent text-start text-base font-medium text-slate-400 hover:text-white hover:bg-white/5 focus:outline-none transition duration-150">
                                خروج
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</nav>