<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">تنظیمات پرداخت کارت‌به‌کارت</h1>
        @if ($loaded)
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                {{ $is_active ? 'فعال' : 'غیرفعال' }}
            </span>
        @endif
    </div>

    @if (! $loaded)
        <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
            تنظیمات پرداخت یافت نشد. لطفاً سیدر را مجدداً اجرا کنید.
        </div>
    @else
        <form wire:submit="save" class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">اطلاعات حساب</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شماره کارت</label>
                        <input type="text" wire:model="card_number" dir="ltr" maxlength="16" placeholder="۱۶ رقم" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition" />
                        @error('card_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">شماره شبا</label>
                        <input type="text" wire:model="iban" dir="ltr" maxlength="26" placeholder="IR24 0000 0000 0000 0000 0000" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition" />
                        @error('iban') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">به نام</label>
                        <input type="text" wire:model="account_name" maxlength="255" placeholder="نام صاحب حساب" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition" />
                        @error('account_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200">
                <h3 class="font-bold text-gray-900 mb-4 p-6 pb-0">پیام‌ها</h3>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">پیام راهنما (نمایش داده‌شده به مشتری)</label>
                        <textarea wire:model="instruction_message" rows="3" maxlength="2000" placeholder="مثال: مبلغ را دقیقاً به این کارت واریز کنید." class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                        @error('instruction_message') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">پیام موفقیت (نمایش پس از ثبت رسید)</label>
                        <textarea wire:model="success_message" rows="3" maxlength="2000" placeholder="مثال: پرداخت شما دریافت شد و در انتظار بررسی است." class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                        @error('success_message') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">وضعیت</h3>
                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-gray-300 text-yellow-500" />
                    <label for="is_active" class="text-sm text-gray-700">پرداخت کارت‌به‌کارت فعال باشد</label>
                </div>
                @error('is_active') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-500 mt-2">با فعال‌سازی، مشتریان امکان ارسال رسید پرداخت کارت‌به‌کارت را خواهند داشت.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-yellow-600 transition">ذخیره</button>
            </div>
        </form>
    @endif
</div>
