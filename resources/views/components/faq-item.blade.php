<div class="bg-white border rounded-2xl overflow-hidden shadow-sm transition-all duration-300"
     :class="open ? 'border-[#ffde5b] ring-2 ring-[#ffde5b]/20 shadow-md' : 'border-slate-200/90 hover:border-slate-300'"
     x-data="{ open: false }">
    <button type="button"
            class="w-full px-5 sm:px-6 py-4 sm:py-4.5 text-start font-bold flex items-center justify-between transition-colors cursor-pointer"
            :class="open ? 'bg-slate-50/60' : 'hover:bg-slate-50/40'"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-controls="faq-answer-{{ $faq->id }}">
        <span class="text-xs sm:text-sm md:text-base leading-snug transition-colors pe-4"
              :class="open ? 'text-[#010619] font-black' : 'text-slate-800 font-bold'">
            {{ $faq->question }}
        </span>
        <span class="p-1.5 rounded-xl transition-all duration-300 shrink-0"
              :class="open ? 'rotate-180 bg-[#ffde5b] text-[#010619] shadow-sm' : 'bg-slate-100 text-slate-500'">
            <x-icons.chevron-down class="w-4 h-4 shrink-0" />
        </span>
    </button>
    <div x-show="open"
         x-collapse
         x-cloak
         id="faq-answer-{{ $faq->id }}"
         class="px-5 sm:px-6 pb-5 pt-1 border-t border-slate-100/80 bg-slate-50/30">
        <div class="text-xs sm:text-sm text-slate-600 pt-3 leading-relaxed">
            {{ $faq->answer }}
        </div>
    </div>
</div>