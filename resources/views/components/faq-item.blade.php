<div class="bg-white border border-gray-200 rounded-xl overflow-hidden" x-data="{ open: false }">
    <button type="button"
            class="w-full px-6 py-4 text-start font-medium text-gray-900 flex items-center justify-between hover:bg-gray-50 transition"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-controls="faq-answer-{{ $faq->id }}">
        <span>{{ $faq->question }}</span>
        <svg class="w-5 h-5 text-gray-400 ms-3 flex-shrink-0 transition-transform duration-200"
             :class="{ 'rotate-180': open }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div x-show="open"
         x-collapse
         x-cloak
         id="faq-answer-{{ $faq->id }}"
         class="px-6 pb-4 border-t border-gray-100">
        <div class="text-gray-600 pt-4 leading-relaxed">
            {!! $faq->answer !!}
        </div>
    </div>
</div>