<div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-sm transition-all duration-200" x-data="{ open: false }">
    <button type="button"
            class="w-full px-6 py-4 text-start font-bold text-slate-800 flex items-center justify-between hover:bg-slate-50/80 transition-colors"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-controls="faq-answer-{{ $faq->id }}">
        <span class="text-sm sm:text-base leading-snug" :class="{ 'text-[#010619]': open }">{{ $faq->question }}</span>
        <span class="p-1 rounded-lg transition-transform duration-200" :class="{ 'rotate-180 bg-[#ffde5b]/20 text-[#010619]': open, 'text-slate-400': !open }">
            <x-icons.chevron-down class="w-5 h-5 shrink-0" />
        </span>
    </button>
    <div x-show="open"
         x-collapse
         x-cloak
         id="faq-answer-{{ $faq->id }}"
         class="px-6 pb-5 border-t border-slate-100 bg-slate-50/40">
        <div class="text-sm text-slate-600 pt-4 leading-relaxed">
            {{ $faq->answer }}
        </div>
    </div>
</div>