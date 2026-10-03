<section class="py-16 sm:py-20 bg-[#010619] relative overflow-hidden border-y border-[#152244]" aria-label="مزایای الواکارت">
    {{-- Ambient radial background glow --}}
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(255,222,91,0.08),rgba(255,255,255,0))]"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <header class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/10 text-[#ffde5b] border border-[#ffde5b]/20 mb-3.5">
                <x-icons.sparkles class="w-3.5 h-3.5 text-[#ffde5b]" />
                <span>مزایای الواکارت</span>
            </span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight leading-tight">
                چرا کارت شخصی الواکارت را انتخاب کنید؟
            </h2>
            <p class="mt-3.5 text-xs sm:text-sm text-slate-300 leading-relaxed">
                ترکیب طراحی منحصربه‌فرد، دقت ساخت صنعتی و فرآیند سفارشی‌سازی شفاف برای داشتن کارتی لوکس و ماندگار
            </p>
        </header>

        {{-- 4 Pillars Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-7">
            {{-- Benefit 1: Custom Design --}}
            <div class="rounded-2xl border border-[#152244] bg-gradient-to-b from-[#152244]/50 to-[#010619] p-6 transition-all duration-300 hover:border-[#ffde5b]/40 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-black/50 group flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-[#ffde5b]/10 border border-[#ffde5b]/20 flex items-center justify-center text-[#ffde5b] mb-5 group-hover:scale-110 group-hover:bg-[#ffde5b] group-hover:text-[#010619] transition-all duration-200 shadow-sm">
                        <x-icons.palette class="w-6 h-6" />
                    </div>
                    <h3 class="text-base font-extrabold text-white mb-2 group-hover:text-[#ffde5b] transition-colors">
                        طراحی کاملاً اختصاصی
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        امکان انتخاب از میان ده‌ها طرح هنری یا شخصی‌سازی و حکاکی نام و طرح دلخواه روی کارت‌های بانکی و سوخت.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-white/5">
                    <a href="{{ route('custom-card.design') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-[#ffde5b] hover:text-white transition-colors">
                        <span>شروع طراحی کارت</span>
                        <x-icons.arrow-left class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>

            {{-- Benefit 2: Quality Production --}}
            <div class="rounded-2xl border border-[#152244] bg-gradient-to-b from-[#152244]/50 to-[#010619] p-6 transition-all duration-300 hover:border-[#ffde5b]/40 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-black/50 group flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-[#ffde5b]/10 border border-[#ffde5b]/20 flex items-center justify-center text-[#ffde5b] mb-5 group-hover:scale-110 group-hover:bg-[#ffde5b] group-hover:text-[#010619] transition-all duration-200 shadow-sm">
                        <x-icons.check-badge class="w-6 h-6" />
                    </div>
                    <h3 class="text-base font-extrabold text-white mb-2 group-hover:text-[#ffde5b] transition-colors">
                        کیفیت ممتاز و ساخت بادوام
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        استفاده از متریال مرغوب فلزی با پوشش ضدخش، چیپست باکیفیت و بالاترین استانداردهای چاپ و مونتاژ.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-white/5">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400">
                        <span>متریال درجه یک فلزی</span>
                    </span>
                </div>
            </div>

            {{-- Benefit 3: Easy Ordering --}}
            <div class="rounded-2xl border border-[#152244] bg-gradient-to-b from-[#152244]/50 to-[#010619] p-6 transition-all duration-300 hover:border-[#ffde5b]/40 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-black/50 group flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-[#ffde5b]/10 border border-[#ffde5b]/20 flex items-center justify-center text-[#ffde5b] mb-5 group-hover:scale-110 group-hover:bg-[#ffde5b] group-hover:text-[#010619] transition-all duration-200 shadow-sm">
                        <x-icons.shopping-bag class="w-6 h-6" />
                    </div>
                    <h3 class="text-base font-extrabold text-white mb-2 group-hover:text-[#ffde5b] transition-colors">
                        سفارش سریع و آسان
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        فرآیند خرید و پرداخت شفاف بدون مراحل پیچیده، با امکان تسویه‌حساب مهمان و ثبت سریع اطلاعات.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-white/5">
                    <a href="{{ route('catalog.products.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-[#ffde5b] hover:text-white transition-colors">
                        <span>مشاهده محصولات</span>
                        <x-icons.arrow-left class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>

            {{-- Benefit 4: Order Tracking --}}
            <div class="rounded-2xl border border-[#152244] bg-gradient-to-b from-[#152244]/50 to-[#010619] p-6 transition-all duration-300 hover:border-[#ffde5b]/40 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-black/50 group flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-[#ffde5b]/10 border border-[#ffde5b]/20 flex items-center justify-center text-[#ffde5b] mb-5 group-hover:scale-110 group-hover:bg-[#ffde5b] group-hover:text-[#010619] transition-all duration-200 shadow-sm">
                        <x-icons.search class="w-6 h-6" />
                    </div>
                    <h3 class="text-base font-extrabold text-white mb-2 group-hover:text-[#ffde5b] transition-colors">
                        پیگیری لحظه‌ای سفارش
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        رهگیری شفاف وضعیت تایید، تولید و ارسال محصول با وارد کردن شماره تماس و کد رهگیری در سامانه.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-white/5">
                    <a href="{{ route('order-tracking.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-[#ffde5b] hover:text-white transition-colors">
                        <span>پیگیری آنلاین سفارش</span>
                        <x-icons.arrow-left class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
