<aside class="fixed inset-y-0 start-0 z-40 flex w-72 flex-col bg-[#010619] text-white transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 lg:transform-none select-none"
       :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'">

    <div class="flex items-center justify-between border-b border-[#152244] px-6 py-5">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-3.5 group">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#ffde5b] text-[#010619] shadow-lg shadow-[#ffde5b]/20 transition-transform group-hover:scale-105">
                <x-application-logo class="h-7 w-7 text-[#010619]" />
            </span>
            <span class="flex flex-col leading-tight">
                <span class="text-base font-bold text-white tracking-tight">ElvaCard</span>
                <span class="text-[11px] text-slate-400 mt-0.5">سامانه مدیریت وب‌سایت</span>
            </span>
        </a>
    </div>

    <nav class="flex-1 space-y-1.5 overflow-y-auto px-4 py-5"
         x-data="{ open: @js($groups) }"
         data-open-groups="{{ implode(' ', $openGroups) }}">

        <a href="{{ route('admin.dashboard') }}" wire:navigate
           class="group relative flex items-center gap-3.5 rounded-2xl px-4 py-3 text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.dashboard') ? 'bg-[#ffde5b] text-[#010619] font-bold shadow-md shadow-[#ffde5b]/20' : 'text-slate-300 hover:bg-white/5 hover:text-[#ffde5b]' }}">
            <x-icons.dashboard class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-[#010619]' : 'text-slate-400 group-hover:text-[#ffde5b]' }}" />
            <span>داشبورد</span>
        </a>

        @foreach ($sidebar as $section)
            @php
                $sectionActive = in_array($currentRoute, $section['routes']);
            @endphp
            <div class="pt-0.5">
                <button type="button"
                        @click="open['{{ $section['id'] }}'] = !open['{{ $section['id'] }}']"
                        :aria-expanded="open['{{ $section['id'] }}'] === true"
                        aria-controls="group-{{ $section['id'] }}"
                        aria-label="{{ $section['label'] }}"
                        class="group flex w-full items-center justify-between gap-2 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-150 {{ $sectionActive ? 'text-white bg-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-[#ffde5b]' }}">
                    <span class="flex items-center gap-3.5">
                        <x-dynamic-component :component="'icons.'.$section['icon']" class="h-5 w-5 shrink-0 {{ $sectionActive ? 'text-[#ffde5b]' : 'text-slate-400 group-hover:text-[#ffde5b]' }}" />
                        <span>{{ $section['label'] }}</span>
                    </span>
                    <x-icons.chevron-down class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" x-bind:class="open['{{ $section['id'] }}'] ? 'rotate-180' : ''" />
                </button>
                <div id="group-{{ $section['id'] }}" x-show="open['{{ $section['id'] }}']" x-collapse.duration.200ms
                     class="mt-1 ms-4 space-y-1 border-s border-[#152244] ps-3">
                    @foreach ($section['items'] as $item)
                        @php
                            $itemActive = request()->routeIs($item['routes']);
                        @endphp
                        <a href="{{ route($item['route']) }}" wire:navigate
                           class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-medium transition-all duration-150 {{ $itemActive ? 'bg-[#ffde5b] text-[#010619] font-bold shadow-sm' : 'text-slate-400 hover:bg-white/5 hover:text-[#ffde5b]' }}">
                            <x-dynamic-component :component="'icons.'.$item['icon']" class="h-4 w-4 shrink-0 {{ $itemActive ? 'text-[#010619]' : 'text-slate-400 group-hover:text-[#ffde5b]' }}" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        <a href="{{ route('admin.orders') }}" wire:navigate
           class="group relative flex items-center gap-3.5 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-150 {{ request()->routeIs('admin.orders') ? 'bg-[#ffde5b] text-[#010619] font-bold shadow-md shadow-[#ffde5b]/20' : 'text-slate-300 hover:bg-white/5 hover:text-[#ffde5b]' }}">
            <x-icons.shopping-bag class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.orders') ? 'text-[#010619]' : 'text-slate-400 group-hover:text-[#ffde5b]' }}" />
            <span>سفارشات</span>
        </a>

        <a href="{{ route('admin.users') }}" wire:navigate
           class="group relative flex items-center gap-3.5 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-150 {{ request()->routeIs('admin.users') ? 'bg-[#ffde5b] text-[#010619] font-bold shadow-md shadow-[#ffde5b]/20' : 'text-slate-300 hover:bg-white/5 hover:text-[#ffde5b]' }}">
            <x-icons.users class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.users') ? 'text-[#010619]' : 'text-slate-400 group-hover:text-[#ffde5b]' }}" />
            <span>کاربران</span>
        </a>

        <a href="{{ route('admin.reports') }}" wire:navigate
           class="group relative flex items-center gap-3.5 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-150 {{ request()->routeIs('admin.reports') ? 'bg-[#ffde5b] text-[#010619] font-bold shadow-md shadow-[#ffde5b]/20' : 'text-slate-300 hover:bg-white/5 hover:text-[#ffde5b]' }}">
            <x-icons.chart-bar class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.reports') ? 'text-[#010619]' : 'text-slate-400 group-hover:text-[#ffde5b]' }}" />
            <span>گزارش‌ها</span>
        </a>
    </nav>

    <div class="border-t border-[#152244] p-4">
        <div class="flex items-center gap-3.5 rounded-2xl bg-[#070e24] border border-[#152244] p-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#ffde5b] text-sm font-bold text-[#010619] shadow-sm">
                {{ mb_substr(auth()->user()?->name ?? 'ا', 0, 1) }}
            </span>
            <div class="min-w-0 flex flex-col">
                <span class="truncate text-sm font-semibold text-white">{{ auth()->user()?->name ?? 'Admin Dev' }}</span>
                <span class="text-xs text-slate-400">مدیر کل سیستم</span>
            </div>
        </div>
        <div class="mt-2.5 flex items-center justify-between gap-2 px-1">
            <a href="{{ route('home') }}" class="flex items-center gap-1.5 text-xs text-slate-400 hover:text-[#ffde5b] transition py-1.5 px-2 rounded-lg hover:bg-white/5">
                <x-icons.globe class="h-4 w-4" />
                <span>بازگشت به سایت</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 text-xs text-rose-400 hover:text-rose-300 transition py-1.5 px-2 rounded-lg hover:bg-rose-500/10">
                    <x-icons.logout class="h-4 w-4" />
                    <span>خروج</span>
                </button>
            </form>
        </div>
    </div>
</aside>