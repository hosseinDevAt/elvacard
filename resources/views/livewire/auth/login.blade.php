<div>
    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold p-3.5 rounded-xl mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-xl font-black text-[#010619]">ورود به حساب کاربری</h2>
        <p class="text-xs text-slate-500 mt-1">شماره تلفن و رمز عبور خود را وارد کنید</p>
    </div>

    <form wire:submit.prevent="login" class="space-y-4">
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره تلفن همراه</label>
            <input type="text" wire:model.blur="phone" placeholder="09123456789" autocomplete="username tel"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition text-left font-mono"
                dir="ltr">
            @error('phone') <p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">رمز عبور</label>
            <input type="password" wire:model.blur="password" placeholder="••••••••" autocomplete="current-password"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition">
            @error('password') <p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="w-full btn-brand-primary py-3 text-sm font-bold shadow-lg shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
            wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">ورود به حساب</span>
            <span wire:loading wire:target="login" class="inline-flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-[#010619]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>در حال ورود...</span>
            </span>
        </button>
    </form>
</div>
