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
            $link = [
                'title' => $menuItem->title,
                'url' => $url,
                'target' => $menuItem->target ?? '_self',
                'active' => false,
            ];

            if (str_starts_with($url, '/')) {
                $currentPath = request()->getPathInfo();
                $link['active'] = ($currentPath === $url || str_starts_with($currentPath, $url . '/'));
            }

            $navItems->push((object) $link);
        }
    }
}

$siteName = site_setting('site_name', config('app.name'));
$siteLogo = site_setting('site_logo');
$cartCount = (int) (app(\App\Services\CartService::class)->getCart()['total_quantity'] ?? 0);
?>
<nav x-data="{ open: false }" class="sticky top-0 z-30 bg-primary-600 border-b border-primary-700 shadow-md">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo + Hamburger -->
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open"
                    aria-label="تغییر وضعیت منو"
                    class="inline-flex items-center p-2 rounded-md text-white/70 hover:text-white hover:bg-white/10 focus:outline-none transition lg:hidden"
                >
                    <x-icons.menu-toggle x-var="open" />
                </button>

                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 shrink-0">
                    @if ($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain">
                    @else
                        <span class="flex items-center justify-center h-10 w-10 rounded-xl bg-accent-500 text-primary-600">
                            <x-icons.card class="h-6 w-6" />
                        </span>
                    @endif
                    <span class="hidden sm:block text-lg font-bold text-white whitespace-nowrap">{{ $siteName }}</span>
                </a>
            </div>

            <!-- Desktop Navigation Links -->
            <div class="hidden lg:flex lg:items-center lg:gap-2">
                @foreach ($navItems as $link)
                    @if ($link->target === '_blank')
                        <x-nav-link :href="str_starts_with($link->url, '/') ? $link->url : $link->url" :active="$link->active" wire:navigate target="{{ $link->target }}" rel="noopener noreferrer">
                            {{ $link->title }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="$link->url" :active="$link->active" wire:navigate>
                            {{ $link->title }}
                        </x-nav-link>
                    @endif
                @endforeach
            </div>

            <!-- Desktop Search -->
            <div class="hidden lg:flex flex-1 items-center justify-center px-6">
                <form method="GET" action="{{ route('catalog.products.index') }}" class="w-full max-w-md">
                    <label class="relative block">
                        <span class="sr-only">جستجو در فروشگاه</span>
                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="جستجو در محصولات..."
                            class="w-full rounded-full border border-white/20 bg-white/10 py-2 ps-4 pe-10 text-sm text-white placeholder-white/60 transition focus:border-accent-400 focus:bg-white focus:text-gray-900 focus:outline-none focus:ring-2 focus:ring-accent-300"
                        >
                        <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                            <x-icons.search class="h-5 w-5 text-white/50" />
                        </span>
                    </label>
                </form>
            </div>

            <!-- Cart + Auth Actions -->
            <div class="flex items-center gap-5">
                <a href="{{ route('cart.index') }}" wire:navigate class="relative inline-flex items-center text-white/80 hover:text-white transition" title="سبد خرید">
                    <x-icons.cart class="h-6 w-6" />
                    @if ($cartCount > 0)
                        <span class="absolute -top-1.5 -end-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-bold leading-none text-primary-600">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>

                @if (Auth::guest())
                    <a href="{{ route('login') }}" wire:navigate class="hidden sm:block text-sm font-medium text-white/80 hover:text-white transition">ورود</a>
                    <a href="{{ route('register') }}" wire:navigate class="hidden sm:block rounded-lg bg-accent-500 px-4 py-2 text-sm font-bold text-primary-600 hover:bg-accent-600 transition">ثبت نام</a>
                @else
                    <div class="hidden sm:block">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white/80 bg-transparent hover:text-white focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ Auth::user()->displayName() }}</div>
                                    <div class="ms-1">
                                        <x-icons.dropdown-chevron />
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                @if (Auth::user()->role === 'admin' && Route::has('admin.dashboard'))
                                    <x-dropdown-link :href="route('admin.dashboard')" wire:navigate>
                                        پنل مدیریت
                                    </x-dropdown-link>
                                @endif

                                <x-dropdown-link :href="route('account.dashboard')" wire:navigate>
                                    حساب کاربری
                                </x-dropdown-link>

                                <x-dropdown-link :href="route('orders.index')" wire:navigate>
                                    سفارش‌های من
                                </x-dropdown-link>

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
                @endif
            </div>
        </div>
    </div>

    <!-- Mobile / Tablet Navigation Panel -->
    <div x-show="open" x-cloak class="border-t border-primary-800 bg-white lg:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 space-y-4">
            <form method="GET" action="{{ route('catalog.products.index') }}">
                <label class="relative block">
                    <span class="sr-only">جستجو در فروشگاه</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="جستجو در محصولات..."
                        class="w-full rounded-full border border-white/20 bg-white/10 py-2 ps-4 pe-10 text-sm text-white placeholder-white/60 transition focus:border-accent-400 focus:bg-white focus:text-gray-900 focus:outline-none focus:ring-2 focus:ring-accent-300"
                    >
                    <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                        <x-icons.search class="h-5 w-5 text-white/50" />
                    </span>
                </label>
            </form>

            @if ($navItems->isNotEmpty())
                <div class="space-y-1">
                    @foreach ($navItems as $link)
                        @if ($link->target === '_blank')
                            <x-responsive-nav-link
                                :href="$link->url"
                                :active="$link->active"
                                target="{{ $link->target }}"
                                rel="noopener noreferrer"
                                @click="open = false"
                            >
                                {{ $link->title }}
                            </x-responsive-nav-link>
                        @else
                            <x-responsive-nav-link
                                :href="$link->url"
                                :active="$link->active"
                                target="{{ $link->target }}"
                                wire:navigate
                                @click="open = false"
                            >
                                {{ $link->title }}
                            </x-responsive-nav-link>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="pt-2 border-t border-gray-100 space-y-1">
                <x-responsive-nav-link :href="route('cart.index')" wire:navigate @click="open = false">
                    سبد خرید @if ($cartCount > 0)({{ $cartCount }})@endif
                </x-responsive-nav-link>

                @if (Auth::guest())
                    <x-responsive-nav-link :href="route('login')" wire:navigate @click="open = false">
                        ورود
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')" wire:navigate @click="open = false">
                        ثبت نام
                    </x-responsive-nav-link>
                @else
                    @if (Auth::user()->role === 'admin' && Route::has('admin.dashboard'))
                        <x-responsive-nav-link :href="route('admin.dashboard')" wire:navigate @click="open = false">
                            پنل مدیریت
                        </x-responsive-nav-link>
                    @endif
                    <x-responsive-nav-link :href="route('account.dashboard')" wire:navigate @click="open = false">
                        حساب کاربری
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('orders.index')" wire:navigate @click="open = false">
                        سفارش‌های من
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('profile.edit')" wire:navigate @click="open = false">
                        پروفایل
                    </x-responsive-nav-link>
                    <div class="pt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full ps-3 pe-4 py-2 border-s-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                خروج
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</nav>