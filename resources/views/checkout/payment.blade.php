@extends('layouts.app')

@section('content')
    <div class="min-h-[70vh] bg-slate-50/60 py-10 sm:py-14">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            {{-- Breadcrumb / Stepper --}}
            <nav class="mb-8 flex items-center justify-center gap-3 text-xs font-medium text-slate-500">
                <span class="flex items-center gap-1.5 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    سبد خرید
                </span>
                <span class="text-slate-300">/</span>
                <span class="flex items-center gap-1.5 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    مشخصات ارسال
                </span>
                <span class="text-slate-300">/</span>
                <span class="flex items-center gap-1.5 font-bold text-[#010619]">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#ffde5b] text-[11px] font-black text-[#010619]">۳</span>
                    مرحله پرداخت
                </span>
            </nav>

            {{-- Failed Payment Alert --}}
            @if ($lastFailedPayment)
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:p-5 text-rose-800 shadow-xs">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="text-sm">
                            <p class="font-bold text-rose-900">پرداخت قبلی شما رد شده است.</p>
                            @if ($setting)
                                <p class="mt-1 text-rose-700">لطفاً رسید پرداخت جدید ارسال کنید تا کارشناسان ما سفارش شما را بررسی و فعال کنند.</p>
                            @else
                                <p class="mt-1 text-rose-700">پرداخت کارت به کارت در حال حاضر فعال نیست. لطفاً بعداً تلاش کنید.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Errors --}}
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:p-5 text-rose-800 shadow-xs">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div class="space-y-1 text-sm font-medium">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Header --}}
            <div class="mb-8 text-center sm:text-right">
                <h1 class="text-2xl font-black text-[#010619] sm:text-3xl">مرحله پرداخت کارت‌به‌کارت</h1>
                <p class="mt-2 text-sm text-slate-600">مبلغ سفارش را به شماره حساب زیر واریز کرده و تصویر فیش واریزی را ثبت کنید.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-12">
                {{-- Left/First Column: Order Summary & Bank Card Info (5 cols) --}}
                <div class="space-y-6 lg:col-span-5">
                    {{-- Order Overview Card --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-xs font-semibold text-slate-500">شماره سفارش</span>
                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-[#010619]">{{ $order->reference }}</span>
                        </div>
                        <div class="mt-4 flex items-baseline justify-between">
                            <span class="text-sm font-medium text-slate-600">مبلغ قابل پرداخت</span>
                            <div class="text-left">
                                <span class="text-2xl font-black text-[#010619]">{{ number_format($order->total_price) }}</span>
                                <span class="mr-1 text-xs font-bold text-slate-500">تومان</span>
                            </div>
                        </div>
                    </div>

                    {{-- Bank Transfer Virtual Card --}}
                    @if ($setting)
                        <div class="relative overflow-hidden rounded-2xl border border-[#152244] bg-gradient-to-br from-[#010619] via-[#091533] to-[#010619] p-6 text-white shadow-xl">
                            {{-- Decorative Background Glow --}}
                            <div class="pointer-events-none absolute -right-12 -top-12 h-36 w-36 rounded-full bg-[#ffde5b]/10 blur-2xl"></div>
                            <div class="pointer-events-none absolute -bottom-12 -left-12 h-36 w-36 rounded-full bg-cyan-500/10 blur-2xl"></div>

                            {{-- Card Brand & Chip --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="h-7 w-10 rounded-md bg-gradient-to-tr from-amber-300 via-amber-400 to-amber-200 shadow-inner flex items-center justify-center">
                                        <div class="h-4 w-6 rounded-xs border border-amber-600/40 grid grid-cols-2 gap-0.5 p-0.5">
                                            <div class="border-b border-amber-600/40"></div>
                                            <div class="border-b border-amber-600/40"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold tracking-wider text-slate-300">کارت‌به‌کارت</span>
                                </div>
                                <span class="rounded-full bg-[#ffde5b]/15 px-2.5 py-0.5 text-[11px] font-bold text-[#ffde5b]">حساب رسمی</span>
                            </div>

                            {{-- Card Number --}}
                            @if ($setting->card_number)
                                <div class="mt-6">
                                    <span class="block text-[11px] font-medium text-slate-400">شماره کارت مقصد</span>
                                    <div class="mt-1 flex items-center justify-between">
                                        <span dir="ltr" class="font-mono text-lg font-bold tracking-widest text-[#ffde5b] sm:text-xl select-all">
                                            {{ $setting->card_number }}
                                        </span>
                                    </div>
                                </div>
                            @endif

                            {{-- Account Holder & IBAN --}}
                            <div class="mt-5 space-y-3 border-t border-white/10 pt-4 text-xs">
                                @if ($setting->account_name)
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">به نام:</span>
                                        <span class="font-bold text-white">{{ $setting->account_name }}</span>
                                    </div>
                                @endif

                                @if ($setting->iban)
                                    <div>
                                        <span class="block text-slate-400">شماره شبا:</span>
                                        <span dir="ltr" class="mt-0.5 block font-mono text-xs font-semibold text-slate-200 select-all break-all">
                                            {{ $setting->iban }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Instruction message --}}
                            @if ($setting->instruction_message)
                                <div class="mt-5 rounded-xl border border-white/10 bg-white/5 p-3 text-xs leading-relaxed text-slate-300">
                                    <div class="flex items-start gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-[#ffde5b] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ $setting->instruction_message }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 shadow-xs">
                            <p class="font-bold">پرداخت کارت‌به‌کارت در حال حاضر فعال نیست.</p>
                            <p class="mt-1 text-xs text-amber-800">سفارش شما با موفقیت ثبت شده و کارشناسان ما به‌زودی جهت هماهنگی پرداخت با شما تماس خواهند گرفت.</p>
                        </div>
                    @endif
                </div>

                {{-- Right/Second Column: Receipt Upload Form (7 cols) --}}
                <div class="lg:col-span-7">
                    @if ($setting)
                        <form method="POST"
                              action="{{ route('checkout.payment.store', $order->token) }}"
                              enctype="multipart/form-data"
                              x-data="receiptUploadHandler()"
                              @submit="onSubmit($event)"
                              class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-6">
                            @csrf
                            <div class="border-b border-slate-100 pb-4">
                                <h2 class="text-lg font-bold text-[#010619]">ثبت فیش واریزی</h2>
                                <p class="mt-1 text-xs text-slate-500">پس از واریز مبلغ، تصویر یا اسکرین‌شات رسید را جهت تأیید بارگذاری کنید.</p>
                            </div>

                            {{-- Receipt Image Upload Zone --}}
                            <div>
                                <label id="receipt_image_label" class="block text-sm font-semibold text-slate-800 mb-2">
                                    تصویر رسید واریز <span class="text-rose-500">*</span>
                                </label>

                                {{-- Native file input, accessible for keyboard and form submission --}}
                                <input id="receipt_image"
                                       name="receipt_image"
                                       type="file"
                                       accept="image/jpeg,image/png,image/webp"
                                       required
                                       class="sr-only"
                                       x-ref="fileInput"
                                       @change="onFileChange($event)"
                                       aria-labelledby="receipt_image_label" />

                                {{-- Full Clickable & Drag-and-Drop Dropzone --}}
                                <div class="relative rounded-2xl border-2 border-dashed p-5 text-center transition-all cursor-pointer select-none"
                                     :class="{
                                         'border-[#010619] bg-[#ffde5b]/15 ring-2 ring-[#ffde5b]': isDragging,
                                         'border-emerald-500 bg-emerald-50/50': fileSelected,
                                         'border-slate-300 bg-slate-50/50 hover:border-[#010619]/40 hover:bg-slate-50': !fileSelected && !isDragging
                                     }"
                                     role="button"
                                     tabindex="0"
                                     aria-controls="receipt_image"
                                     @click="$refs.fileInput.click()"
                                     @keydown.enter.prevent="$refs.fileInput.click()"
                                     @keydown.space.prevent="$refs.fileInput.click()"
                                     @dragover.prevent="isDragging = true"
                                     @dragleave.prevent="isDragging = false"
                                     @drop.prevent="onDrop($event)">

                                    {{-- Unselected State --}}
                                    <div x-show="!fileSelected" class="space-y-2 pointer-events-none">
                                        <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="text-sm text-slate-600">
                                            <span class="font-bold text-[#010619]">انتخاب فایل تصویر</span>
                                            <span class="pr-1 text-xs text-slate-500">یا کشیدن و رها کردن در این بخش</span>
                                        </div>
                                        <p class="text-xs text-slate-400">فقط فرمت‌های JPG، PNG، WEBP (حداکثر ۵ مگابایت)</p>
                                    </div>

                                    {{-- Selected State: Clear Feedback --}}
                                    <div x-show="fileSelected" x-cloak class="space-y-3" @click.stop>
                                        <div class="flex items-center justify-between gap-3 bg-white p-3 rounded-xl border border-emerald-200 shadow-2xs">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                                <div class="text-right min-w-0">
                                                    <p class="text-xs font-bold text-slate-900 truncate" x-text="fileName" dir="ltr"></p>
                                                    <span class="inline-block rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 mt-0.5" x-text="fileSize"></span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <button type="button"
                                                        @click="$refs.fileInput.click()"
                                                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                                                    تغییر
                                                </button>
                                                <button type="button"
                                                        @click="clearFile()"
                                                        class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 hover:bg-rose-100 transition cursor-pointer">
                                                    حذف
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-[11px] font-medium text-emerald-800 bg-emerald-50/80 rounded-lg p-2 text-center border border-emerald-100">
                                            ✓ فایل تصویر انتخاب شد و آماده ارسال است (هنوز ثبت نهایی نشده است؛ جهت تکمیل، دکمه «ثبت رسید پرداخت» را بزنید).
                                        </p>
                                    </div>
                                </div>
                                @error('receipt_image') <p class="admin-error mt-2">{{ $message }}</p> @enderror
                            </div>

                            {{-- Tracking Number --}}
                            <div>
                                <label for="tracking_number" class="block text-sm font-semibold text-slate-800">
                                    شماره پیگیری پرداخت <span class="text-xs font-normal text-slate-400">(اختیاری)</span>
                                </label>
                                <input id="tracking_number" name="tracking_number" type="text" value="{{ old('tracking_number') }}" maxlength="100" class="mt-2 block w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:border-[#ffde5b] focus:ring-2 focus:ring-[#ffde5b]/30 focus:outline-hidden transition" dir="ltr" placeholder="مثال: 94827103" />
                                <p class="mt-1 text-xs text-slate-500">شماره ارجاع، پیگیری یا کد تراکنش رسید بانکی</p>
                            </div>

                            {{-- Note --}}
                            <div>
                                <label for="note" class="block text-sm font-semibold text-slate-800">
                                    توضیحات تکمیلی <span class="text-xs font-normal text-slate-400">(اختیاری)</span>
                                </label>
                                <textarea id="note" name="note" rows="3" maxlength="1000" class="mt-2 block w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:border-[#ffde5b] focus:ring-2 focus:ring-[#ffde5b]/30 focus:outline-hidden transition" placeholder="در صورت نیاز توضیحی برای تیم پشتیبانی بنویسید...">{{ old('note') }}</textarea>
                            </div>

                            {{-- Submit Button with Submitting/Loading State --}}
                            <button type="submit"
                                    :disabled="isSubmitting"
                                    :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-[#f5d347] active:scale-[0.99] cursor-pointer'"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#ffde5b] py-3.5 px-6 text-sm font-bold text-[#010619] shadow-md shadow-[#ffde5b]/20 transition">
                                <span x-show="!isSubmitting" class="inline-flex items-center gap-2">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>ثبت رسید پرداخت</span>
                                </span>
                                <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-[#010619]" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>در حال ارسال و ثبت رسید پرداخت...</span>
                                </span>
                            </button>
                        </form>

                        <script>
                            function receiptUploadHandler() {
                                return {
                                    isDragging: false,
                                    fileSelected: false,
                                    fileName: '',
                                    fileSize: '',
                                    isSubmitting: false,

                                    init() {
                                        window.addEventListener('pageshow', () => {
                                            this.isSubmitting = false;
                                        });
                                    },

                                    formatBytes(bytes) {
                                        if (!bytes || bytes === 0) return '0 بایت';
                                        const k = 1024;
                                        const units = ['بایت', 'کیلوبایت', 'مگابایت'];
                                        const i = Math.floor(Math.log(bytes) / Math.log(k));
                                        const val = (bytes / Math.pow(k, i)).toFixed(1);
                                        return val + ' ' + (units[i] || 'مگابایت');
                                    },

                                    onFileChange(e) {
                                        const files = e.target.files;
                                        if (files && files.length > 0) {
                                            this.setFile(files[0]);
                                        } else {
                                            this.clearFile();
                                        }
                                    },

                                    onDrop(e) {
                                        this.isDragging = false;
                                        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                                            const file = e.dataTransfer.files[0];
                                            const input = this.$refs.fileInput;
                                            try {
                                                const dt = new DataTransfer();
                                                dt.items.add(file);
                                                input.files = dt.files;
                                            } catch (err) {
                                                // Fallback if DataTransfer not supported
                                            }
                                            this.setFile(file);
                                        }
                                    },

                                    setFile(file) {
                                        this.fileName = file.name;
                                        this.fileSize = this.formatBytes(file.size);
                                        this.fileSelected = true;
                                    },

                                    clearFile() {
                                        this.fileSelected = false;
                                        this.fileName = '';
                                        this.fileSize = '';
                                        if (this.$refs.fileInput) {
                                            this.$refs.fileInput.value = '';
                                        }
                                    },

                                    onSubmit(e) {
                                        if (this.isSubmitting) {
                                            e.preventDefault();
                                            return;
                                        }
                                        this.isSubmitting = true;
                                    }
                                };
                            }
                        </script>
                    @else
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs text-center space-y-4">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-[#010619]">سفارش شما در سیستم ثبت شد</h3>
                                <p class="mt-1 text-sm text-slate-600">می‌توانید آخرین وضعیت سفارش خود را از طریق پیگیری سفارش بررسی نمایید.</p>
                            </div>
                            <a href="{{ route('checkout.success', $order->token) }}" wire:navigate class="inline-flex items-center justify-center rounded-xl bg-[#010619] px-6 py-3 text-sm font-bold text-white hover:bg-slate-800 transition">
                                مشاهده وضعیت سفارش
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection