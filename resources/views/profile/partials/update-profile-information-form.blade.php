<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            اطلاعات حساب
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            اطلاعات شخصی و آدرس ارسال سفارش خود را به‌روزرسانی کنید.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="first_name" :value="'نام'" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>

            <div>
                <x-input-label for="last_name" :value="'نام خانوادگی'" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>
        </div>

        <div>
            <x-input-label for="phone" :value="'شماره موبایل'" />
            <x-text-input id="phone" type="text" class="mt-1 block w-full bg-gray-100" :value="$user->phone" disabled dir="ltr" />
            <p class="mt-1 text-xs text-gray-400">شماره موبایل شناسه ورود شماست و قابل تغییر نیست.</p>
        </div>

        <div>
            <x-input-label for="email" :value="'ایمیل (اختیاری)'" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" autocomplete="username" dir="ltr" placeholder="you@example.com" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="postal_code" :value="'کد پستی'" />
                <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full" :value="old('postal_code', $user->postal_code)" autocomplete="postal-code" dir="ltr" maxlength="10" placeholder="1234567890" />
                <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
            </div>

            <div>
                <x-input-label for="plaque" :value="'پلاک'" />
                <x-text-input id="plaque" name="plaque" type="text" class="mt-1 block w-full" :value="old('plaque', $user->plaque)" maxlength="50" placeholder="۱۲" />
                <x-input-error class="mt-2" :messages="$errors->get('plaque')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>ذخیره</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >ذخیره شد.</p>
            @endif
        </div>
    </form>
</section>