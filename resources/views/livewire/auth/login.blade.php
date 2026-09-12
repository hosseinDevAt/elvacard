<div>
    @if (session()->has('error'))
        <div class="bg-red-50 text-red-600 text-sm p-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    <h2 class="text-xl font-bold text-gray-900 mb-1">ورود به حساب</h2>
    <p class="text-sm text-gray-500 mb-6">شماره تلفن و رمز عبور خود را وارد کنید</p>

    <form wire:submit.prevent="login" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">شماره تلفن</label>
            <input type="text" wire:model.blur="phone" placeholder="09123456789"
                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-left"
                dir="ltr">
            @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">رمز عبور</label>
            <input type="password" wire:model.blur="password" placeholder="رمز عبور"
                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="w-full bg-yellow-500 text-white py-3 rounded-xl font-bold hover:bg-yellow-600 transition disabled:opacity-50"
            wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">ورود</span>
            <span wire:loading wire:target="login">در حال ورود...</span>
        </button>
    </form>

    <div class="mt-6 text-center space-y-2">
        <p class="text-xs text-gray-400">رمز عبور خود را فراموش کرده‌اید؟ <a href="{{ route('password.request') }}" class="text-yellow-600 hover:underline">بازیابی رمز</a></p>
    </div>
</div>
