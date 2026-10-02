<div>
    <x-admin.page-header title="تنظیمات پرداخت کارت‌به‌کارت" subtitle="پیکربندی شماره کارت، شبا و پیام‌های راهنمای پرداخت دستی مشتریان">
        <x-slot:actions>
            @if ($loaded)
                <span class="admin-badge {{ $is_active ? 'admin-badge-success' : 'admin-badge-danger' }}">
                    {{ $is_active ? 'پرداخت کارت‌به‌کارت فعال' : 'پرداخت کارت‌به‌کارت غیرفعال' }}
                </span>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if (! $loaded)
        <div class="admin-card p-8">
            <x-admin.empty-state title="تنظیمات پرداخت یافت نشد" description="تنظیمات پرداخت دستی در پایگاه داده مقداردهی نشده است. لطفاً سیدر را مجدداً اجرا کنید." />
        </div>
    @else
        <form wire:submit="save" class="space-y-6">
            <div class="admin-card p-6">
                <h3 class="font-bold text-slate-900 mb-4">اطلاعات حساب بانکی</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">شماره کارت</label>
                        <input type="text" wire:model="card_number" dir="ltr" maxlength="16" placeholder="۱۶ رقم" class="admin-input font-mono" />
                        @error('card_number') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">شماره شبا</label>
                        <input type="text" wire:model="iban" dir="ltr" maxlength="26" placeholder="IR24 0000 0000 0000 0000 0000" class="admin-input font-mono" />
                        @error('iban') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label">به نام (صاحب حساب)</label>
                        <input type="text" wire:model="account_name" maxlength="255" placeholder="نام صاحب حساب" class="admin-input" />
                        @error('account_name') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="admin-card p-6">
                <h3 class="font-bold text-slate-900 mb-4">پیام‌های راهنما و رسید</h3>
                <div class="space-y-4">
                    <div>
                        <label class="admin-label">پیام راهنما (نمایش داده‌شده به مشتری هنگام پرداخت)</label>
                        <textarea wire:model="instruction_message" rows="3" maxlength="2000" placeholder="مثال: مبلغ را دقیقاً به این شماره کارت واریز کرده و تصویر فیش را بارگذاری کنید." class="admin-input"></textarea>
                        @error('instruction_message') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">پیام موفقیت (نمایش پس از ثبت رسید توسط مشتری)</label>
                        <textarea wire:model="success_message" rows="3" maxlength="2000" placeholder="مثال: پرداخت شما با موفقیت ثبت شد و پس از بررسی واحد مالی، سفارش شما وارد مرحله تولید خواهد شد." class="admin-input"></textarea>
                        @error('success_message') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="admin-card p-6">
                <h3 class="font-bold text-slate-900 mb-4">وضعیت فعال‌سازی</h3>
                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]" />
                    <label for="is_active" class="text-sm font-semibold text-slate-700">پرداخت کارت‌به‌کارت در درگاه فروشگاه فعال باشد</label>
                </div>
                @error('is_active') <p class="admin-error">{{ $message }}</p> @enderror
                <p class="text-xs text-slate-500 mt-2">با فعال‌سازی این گزینه، مشتریان می‌توانند در صفحه تسویه‌حساب گزینه کارت‌به‌کارت را انتخاب و فیش واریز را بارگذاری نمایند.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="admin-btn admin-btn-primary font-semibold">ذخیره تغییرات</button>
            </div>
        </form>
    @endif
</div>
