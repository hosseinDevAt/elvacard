<div>
    <div class="mb-6">
        <h2 class="text-xl font-black text-[#010619]">بازیابی رمز عبور</h2>
        <p class="text-xs text-slate-500 mt-1">با کد تأیید پیامکی رمز عبور جدید تنظیم کنید</p>
    </div>

    @if (session()->has('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold p-3.5 rounded-xl mb-4">{{ session('status') }}</div>
    @endif

    {{-- Step indicator --}}
    <div class="flex items-center gap-2 mb-6 text-xs">
        @foreach ([1 => 'شماره', 2 => 'تأیید کد', 3 => 'رمز جدید'] as $no => $label)
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-full font-bold {{ $step === $no ? 'bg-[#ffde5b] text-[#010619] shadow-xs' : ($step > $no ? 'bg-[#010619] text-white' : 'bg-slate-200 text-slate-500') }}">{{ $no }}</span>
                <span class="{{ $step === $no ? 'text-[#010619] font-bold' : 'text-slate-400' }} hidden sm:inline">{{ $label }}</span>
            </div>
            @if ($no < 3)
                <span class="h-px w-6 {{ $step > $no ? 'bg-[#010619]' : 'bg-slate-200' }}"></span>
            @endif
        @endforeach
    </div>

    @if ($step === 1)
        <p class="text-xs text-slate-600 mb-4">شماره موبایل خود را وارد کنید؛ کد تأیید برای شما ارسال خواهد شد.</p>

        <form wire:submit.prevent="requestOtp" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره موبایل</label>
                <input type="tel" wire:model.blur="phone" placeholder="09123456789" inputmode="numeric" autocomplete="tel"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition text-left font-mono"
                    dir="ltr">
                @error('phone') <p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full btn-brand-primary py-3 text-sm font-bold shadow-lg shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50 cursor-pointer">
                دریافت کد تأیید
            </button>
        </form>
    @endif

    @if ($step === 2)
        <p class="text-xs text-slate-600 mb-4">کد تأیید به شماره <span dir="ltr" class="font-bold text-[#010619] font-mono">{{ $phone }}</span> ارسال شد.</p>

        <form wire:submit.prevent="verifyCode" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">کد تأیید ۶ رقمی</label>
                <input type="text" wire:model="code" placeholder="123456" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition text-center text-xl font-mono tracking-widest text-[#010619]"
                    dir="ltr">
                @error('code') <p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full btn-brand-primary py-3 text-sm font-bold shadow-lg shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50 cursor-pointer">
                تأیید کد
            </button>
        </form>

        <div class="mt-4 flex items-center justify-between text-xs">
            <button type="button" wire:click="requestOtp"
                class="font-bold text-slate-700 hover:text-[#010619] transition cursor-pointer">ارسال مجدد کد</button>
            <button type="button" wire:click="$set('step', 1)"
                class="text-slate-400 hover:text-slate-600 transition cursor-pointer">تغییر شماره</button>
        </div>
    @endif

    @if ($step === 3)
        <p class="text-xs text-slate-600 mb-4">برای <span dir="ltr" class="font-bold text-[#010619] font-mono">{{ $phone }}</span> رمز عبور جدید تعیین کنید.</p>

        <form wire:submit.prevent="completeReset" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">رمز عبور جدید</label>
                <input type="password" wire:model="password" autocomplete="new-password"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition">
                @error('password') <p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">تکرار رمز عبور جدید</label>
                <input type="password" wire:model="passwordConfirmation" autocomplete="new-password"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition">
            </div>

            <button type="submit"
                class="w-full btn-brand-primary py-3 text-sm font-bold shadow-lg shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50 cursor-pointer">
                ثبت رمز عبور جدید
            </button>
        </form>
    @endif

    <div class="mt-6 text-center">
        <p class="text-xs text-slate-500">رمز عبور را به خاطر آوردید؟ <a href="{{ route('login') }}" class="font-bold text-[#010619] hover:underline">ورود به حساب</a></p>
    </div>
</div>