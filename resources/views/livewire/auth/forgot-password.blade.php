<div>
    <h2 class="text-xl font-bold text-gray-900 mb-1">بازیابی رمز عبور</h2>
    <p class="text-sm text-gray-500 mb-6">با کد تأیید پیامکی رمز عبور جدید تنظیم کنید</p>

    @if (session()->has('status'))
        <div class="bg-green-50 text-green-700 text-sm p-3 rounded-lg mb-4">{{ session('status') }}</div>
    @endif

    {{-- Step indicator --}}
    <div class="flex items-center gap-2 mb-6 text-xs">
        @foreach ([1 => 'شماره', 2 => 'تأیید کد', 3 => 'رمز جدید'] as $no => $label)
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-full font-bold {{ $step === $no ? 'bg-accent-500 text-primary-600' : ($step > $no ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500') }}">{{ $no }}</span>
                <span class="{{ $step === $no ? 'text-gray-800 font-medium' : 'text-gray-400' }} hidden sm:inline">{{ $label }}</span>
            </div>
            @if ($no < 3)
                <span class="h-px w-6 {{ $step > $no ? 'bg-primary-600' : 'bg-gray-200' }}"></span>
            @endif
        @endforeach
    </div>

    @if ($step === 1)
        <p class="text-sm text-gray-600 mb-4">شماره موبایل خود را وارد کنید؛ کد تأیید برای شما ارسال خواهد شد.</p>

        <form wire:submit.prevent="requestOtp" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل</label>
                <input type="tel" wire:model.blur="phone" placeholder="09123456789" inputmode="numeric"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-200 transition text-left"
                    dir="ltr">
                @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full bg-accent-500 text-primary-600 py-3 rounded-xl font-bold hover:bg-accent-600 transition disabled:opacity-50">
                دریافت کد تأیید
            </button>
        </form>
    @endif

    @if ($step === 2)
        <p class="text-sm text-gray-600 mb-4">کد تأیید به شماره <span dir="ltr" class="font-bold">{{ $phone }}</span> ارسال شد.</p>

        <form wire:submit.prevent="verifyCode" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">کد تأیید</label>
                <input type="text" wire:model="code" placeholder="123456" inputmode="numeric" maxlength="6"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-200 transition text-center text-xl tracking-widest"
                    dir="ltr">
                @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full bg-accent-500 text-primary-600 py-3 rounded-xl font-bold hover:bg-accent-600 transition disabled:opacity-50">
                تأیید کد
            </button>
        </form>

        <div class="mt-4 flex items-center justify-between text-sm">
            <button type="button" wire:click="requestOtp"
                class="text-primary-600 hover:text-primary-800 transition">ارسال مجدد کد</button>
            <button type="button" wire:click="$set('step', 1)"
                class="text-gray-500 hover:text-gray-700 transition">تغییر شماره</button>
        </div>
    @endif

    @if ($step === 3)
        <p class="text-sm text-gray-600 mb-4">برای <span dir="ltr" class="font-bold">{{ $phone }}</span> رمز عبور جدید تعیین کنید.</p>

        <form wire:submit.prevent="completeReset" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">رمز عبور جدید</label>
                <input type="password" wire:model="password" autocomplete="new-password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-200 transition">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">تکرار رمز عبور جدید</label>
                <input type="password" wire:model="passwordConfirmation" autocomplete="new-password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-200 transition">
            </div>

            <button type="submit"
                class="w-full bg-accent-500 text-primary-600 py-3 rounded-xl font-bold hover:bg-accent-600 transition disabled:opacity-50">
                ثبت رمز عبور جدید
            </button>
        </form>
    @endif

    <div class="mt-6 text-center space-y-2">
        <p class="text-xs text-gray-400">رمز عبور را به خاطر آوردید؟ <a href="{{ route('login') }}" class="text-primary-600 hover:underline">ورود</a></p>
    </div>
</div>