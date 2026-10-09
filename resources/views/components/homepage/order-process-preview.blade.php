@if (\App\Models\OrderProcessGuide::tablesExist())
<section class="py-16 sm:py-20 bg-slate-50/80 relative overflow-hidden border-t border-slate-200/90" aria-label="راهنمای مراحل سفارش">
    {{-- Ambient radial background glow --}}
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_70%_50%_at_50%_0%,rgba(255,222,91,0.14),transparent)]"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <header class="text-center max-w-2xl mx-auto mb-8 sm:mb-12">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/20 text-[#010619] border border-[#ffde5b]/40 mb-3">
                <x-icons.sparkles class="w-3.5 h-3.5 text-[#010619]" />
                <span>شفافیت و امنیت فرآیند</span>
            </span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                مراحل ساخت و تحویل کارت شما چگونه است؟
            </h2>
            <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-xl mx-auto">
                مسیر شفاف و گام‌به‌گام ثبت، شخصی‌سازی، تولید دقیق صنعتی و تحویل امن کارت‌های بانکی فلزی و کارت سوخت
            </p>
        </header>

        {{-- 2 Workflow Previews Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 max-w-4xl mx-auto">
            {{-- Workflow 1: Metallic Bank Card --}}
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 flex flex-col justify-between transition-all duration-300 hover:border-amber-400/60 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50 group">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-700 shadow-2xs group-hover:scale-105 group-hover:bg-[#010619] group-hover:text-[#ffde5b] group-hover:border-[#010619] transition-all">
                            <x-icons.credit-card class="w-6 h-6" />
                        </div>
                        <span class="text-[11px] font-bold text-amber-800 bg-amber-400/15 border border-amber-400/30 px-2.5 py-0.5 rounded-lg">کارت بانکی فلزی</span>
                    </div>

                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 mb-2 group-hover:text-[#010619] transition-colors">
                        سفارش کارت فلزی لوکس
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
                        شخصی‌سازی رنگ و طرح، بارگذاری مشخصات دلخواه، انتقال چیپست هوشمند با بالاترین امنیت و ارسال در جعبه اختصاصی.
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                    <a href="{{ route('order-process.index', ['workflow' => 'bank_card']) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-slate-900 group-hover:text-amber-700 transition-colors">
                        <span>مشاهده مراحل کامل</span>
                        <x-icons.arrow-left class="w-3.5 h-3.5 text-amber-600 transition-transform group-hover:-translate-x-1" />
                    </a>
                    <a href="{{ route('custom-card.bank') }}" wire:navigate class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-[#010619] hover:text-[#ffde5b] transition-all">
                        ورود به طراح
                    </a>
                </div>
            </div>

            {{-- Workflow 2: Fuel Card --}}
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 flex flex-col justify-between transition-all duration-300 hover:border-amber-400/60 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50 group">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-700 shadow-2xs group-hover:scale-105 group-hover:bg-[#010619] group-hover:text-[#ffde5b] group-hover:border-[#010619] transition-all">
                            <x-icons.card class="w-6 h-6" />
                        </div>
                        <span class="text-[11px] font-bold text-amber-800 bg-amber-400/15 border border-amber-400/30 px-2.5 py-0.5 rounded-lg">کارت سوخت</span>
                    </div>

                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 mb-2 group-hover:text-[#010619] transition-colors">
                        سفارش کارت سوخت اختصاصی
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
                        طراحی بادوام و مقاوم در برابر سایش با متریال باکیفیت، بررسی اطلاعات پلاک و چیپست، و تولید سریع استاندارد.
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                    <a href="{{ route('order-process.index', ['workflow' => 'fuel_card']) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-slate-900 group-hover:text-amber-700 transition-colors">
                        <span>مشاهده مراحل کامل</span>
                        <x-icons.arrow-left class="w-3.5 h-3.5 text-amber-600 transition-transform group-hover:-translate-x-1" />
                    </a>
                    <a href="{{ route('custom-card.fuel') }}" wire:navigate class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-[#010619] hover:text-[#ffde5b] transition-all">
                        ورود به طراح
                    </a>
                </div>
            </div>
        </div>

        {{-- Bottom Action --}}
        <div class="mt-8 sm:mt-10 text-center">
            <a href="{{ route('order-process.index') }}" wire:navigate class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-slate-200/90 bg-white hover:bg-slate-50 text-xs sm:text-sm font-bold text-slate-800 hover:text-amber-700 hover:border-amber-400/50 transition-all shadow-xs hover:shadow-md">
                <span>مطالعه راهنمای جامع مراحل سفارش و زمان‌بندی تحویل</span>
                <x-icons.arrow-left class="w-4 h-4 text-amber-600" />
            </a>
        </div>
    </div>
</section>
@endif
