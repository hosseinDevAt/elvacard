<section class="space-y-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($catalog as $design)
            <div
                id="design-{{ $design['id'] }}"
                class="group store-card p-5 scroll-mt-24 flex flex-col justify-between"
            >
                <div class="overflow-hidden rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition group-hover:border-slate-200">
                    @if ($design['preview_image_path'])
                        <img
                            src="{{ asset('storage/' . $design['preview_image_path']) }}"
                            alt="{{ $design['name'] }}"
                            class="mx-auto max-h-44 w-full object-contain transition duration-300 group-hover:scale-105"
                            loading="lazy"
                        >
                    @else
                        <div class="flex h-44 flex-col items-center justify-center text-slate-400">
                            <x-icons.image-placeholder class="h-10 w-10 opacity-40 mb-2" />
                            <p class="text-xs">تصویر فعالی وجود ندارد</p>
                        </div>
                    @endif
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-[#010619] group-hover:text-slate-800 transition">{{ $design['name'] }}</h3>
                        <p class="mt-0.5 text-[11px] font-mono text-slate-400 truncate max-w-[200px]" dir="ltr">{{ basename($design['preview_image_path'] ?? '') }}</p>
                    </div>

                    <a href="{{ route('custom-card.design') }}" wire:navigate class="rounded-lg bg-slate-100 p-2 text-slate-600 hover:bg-[#ffde5b] hover:text-[#010619] transition" title="طراحی کارت با این طرح">
                        <x-icons.arrow-left class="h-4 w-4" />
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-xs">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <x-icons.palette class="h-7 w-7" />
                </div>
                <h3 class="mt-4 text-base font-bold text-[#010619]">داده‌ای برای فهرست طرح‌ها یافت نشد</h3>
                <p class="mt-1 text-xs text-slate-500">طرح فعالی برای نمایش در این بخش یا دسته‌بندی وجود ندارد.</p>
            </div>
        @endforelse
    </div>

    @if ($catalog->hasPages())
        <div class="pt-4">
            {{ $catalog->links() }}
        </div>
    @endif
</section>