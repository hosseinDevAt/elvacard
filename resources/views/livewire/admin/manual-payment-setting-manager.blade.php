<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        @if ($loaded)
            <span class="admin-badge {{ $is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                {{ $is_active ? 'فعال' : 'غیرفعال' }}
            </span>
        @endif
    </div>

    @if (! $loaded)
        <div class="admin-card p-8 text-center text-gray-400">
            تنظیمات پرداخت یافت نشد. لطفاً سیدر را مجدداً اجرا کنید.
        </div>
    @else
        <form wire:submit="save" class="space-y-6">
            <div class="admin-card p-6">
                <h3 class="font-bold text-gray-900 mb-4">اطلاعات حساب</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">شماره کارت</label>
                        <input type="text" wire:model="card_number" dir="ltr" maxlength="16" placeholder="۱۶ رقم" class="admin-input" />
                        @error('card_number') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">شماره شبا</label>
                        <input type="text" wire:model="iban" dir="ltr" maxlength="26" placeholder="IR24 0000 0000 0000 0000 0000" class="admin-input" />
                        @error('iban') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label">به نام</label>
                        <input type="text" wire:model="account_name" maxlength="255" placeholder="نام صاحب حساب" class="admin-input" />
                        @error('account_name') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h3 class="font-bold text-gray-900 mb-4 p-6 pb-0">پیام‌ها</h3>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="admin-label">پیام راهنما (نمایش داده‌شده به مشتری)</label>
                        <textarea wire:model="instruction_message" rows="3" maxlength="2000" placeholder="مثال: مبلغ را دقیقاً به این کارت واریز کنید." class="admin-input"></textarea>
                        @error('instruction_message') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">پیام موفقیت (نمایش پس از ثبت رسید)</label>
                        <textarea wire:model="success_message" rows="3" maxlength="2000" placeholder="مثال: پرداخت شما دریافت شد و در انتظار بررسی است." class="admin-input"></textarea>
                        @error('success_message') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="admin-card p-6">
                <h3 class="font-bold text-gray-900 mb-4">وضعیت</h3>
                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-gray-300 text-yellow-500" />
                    <label for="is_active" class="text-sm text-gray-700">پرداخت کارت‌به‌کارت فعال باشد</label>
                </div>
                @error('is_active') <p class="admin-error">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-500 mt-2">با فعال‌سازی، مشتریان امکان ارسال رسید پرداخت کارت‌به‌کارت را خواهند داشت.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-yellow-600 transition">ذخیره</button>
            </div>
        </form>
    @endif
</div>
