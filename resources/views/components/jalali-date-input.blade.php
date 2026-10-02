@php
    $wireModel = $attributes->wire('model');
    $modelName = $wireModel->value() ?? 'jalali-date';
    $inputId = $attributes->get('id') ?? 'jalali_' . mb_substr(md5($modelName), 0, 12);
    $mode = $mode ?? 'date';
    $inputAttributes = $attributes->except(['class', 'id', 'name']);
    $classes = trim('w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-800 transition focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 ' . ($attributes->get('class') ?? ''));
@endphp

<div
    x-data="jalaliCalendar({
        mode: '{{ $mode }}',
        min: '{{ $min ?? '' }}',
        max: '{{ $max ?? '' }}',
    })"
    class="relative"
    wire:key="jalali-input-{{ $modelName }}"
>
    {{-- Canonical ASCII Jalali value bound to Livewire. --}}
    <input
        type="hidden"
        x-ref="hidden"
        value=""
        {{ $inputAttributes }}
    >

    <div class="relative">
        <input
            type="text"
            id="{{ $inputId }}"
            inputmode="text"
            autocomplete="off"
            x-ref="visible"
            :value="display"
            @input="handleManualInput($event.target.value)"
            @focus="open = true"
            placeholder="{{ $mode === 'datetime' ? '۱۴۰۵/۰۶/۲۵ ۱۴:۳۰' : '۱۴۰۵/۰۶/۲۵' }}"
            dir="rtl"
            class="{{ $classes }}"
        >
        <button
            type="button"
            @click="open = !open"
            class="absolute inset-y-0 left-0 flex items-center px-3 text-slate-400 hover:text-[#010619] transition"
            aria-label="باز کردن تقویم"
            tabindex="-1"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </button>
    </div>

    {{-- Calendar popup --}}
    <div
        x-show="open"
        x-cloak
        x-transition
        @click.outside="open = false"
        class="absolute z-30 mt-1 w-72 rounded-2xl border border-slate-200/90 bg-white p-3.5 shadow-xl"
        style="display: none;"
    >
        {{-- Header nav --}}
        <div class="flex items-center justify-between mb-2">
            <button type="button" @click="prevMonth()" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition font-bold">»</button>
            <div class="text-sm font-bold text-slate-900">
                <span x-text="monthNames[viewMonth]"></span>
                <span x-text="toPd(viewYear)"></span>
            </div>
            <button type="button" @click="nextMonth()" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition font-bold">«</button>
        </div>

        {{-- Weekdays header (Saturday-first) --}}
        <div class="grid grid-cols-7 mb-1">
            <template x-for="d in weekDays" :key="d">
                <div class="h-8 flex items-center justify-center text-[10px] font-bold text-slate-400" x-text="d"></div>
            </template>
        </div>

        {{-- Day grid --}}
        <div class="grid grid-cols-7 gap-0.5">
            <template x-for="(cell, index) in grid" :key="index">
                <button
                    type="button"
                    x-show="cell"
                    :disabled="!cell || cell.disabled"
                    x-on:click="cell && !cell.disabled && pickDay(viewYear, viewMonth, cell.day)"
                    class="h-8 rounded-lg text-xs font-medium transition disabled:opacity-30 disabled:cursor-not-allowed"
                    :class="(cell && cell.selected)
                        ? 'bg-[#ffde5b] text-[#010619] font-bold shadow-sm'
                        : ((cell && cell.isToday) ? 'ring-1 ring-[#010619] text-[#010619] font-bold hover:bg-[#ffde5b]/20' : 'hover:bg-slate-100 text-slate-800')"
                    x-text="cell ? toPd(cell.day) : ''"
                ></button>
            </template>
        </div>

        {{-- Time picker (datetime mode) --}}
        <div x-show="mode === 'datetime' && selected" class="mt-3 flex items-end justify-center gap-2 border-t border-slate-100 pt-3" x-cloak>
            <div class="flex flex-col items-center">
                <label class="text-[10px] text-slate-400 mb-1">ساعت</label>
                <select x-model.number="hours" @change="refreshTime()" class="rounded-lg border border-slate-300 text-xs px-2 py-1 text-slate-800 focus:border-[#010619] focus:ring-1 focus:ring-[#ffde5b]">
                    <template x-for="h in 24" :key="h">
                        <option :value="h - 1" x-text="toPd(String(h - 1).padStart(2, '0'))"></option>
                    </template>
                </select>
            </div>
            <div class="flex flex-col items-center">
                <label class="text-[10px] text-slate-400 mb-1">دقیقه</label>
                <select x-model.number="minutes" @change="refreshTime()" class="rounded-lg border border-slate-300 text-xs px-2 py-1 text-slate-800 focus:border-[#010619] focus:ring-1 focus:ring-[#ffde5b]">
                    <template x-for="m in 60" :key="m">
                        <option :value="m - 1" x-text="toPd(String(m - 1).padStart(2, '0'))"></option>
                    </template>
                </select>
            </div>
        </div>

        {{-- Footer actions --}}
        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2">
            <button type="button" @click="gotoToday()" class="text-xs font-bold text-[#010619] hover:underline transition">امروز</button>
            <div class="flex items-center gap-2">
                <button type="button" @click="clearValue()" class="text-xs font-medium text-rose-600 hover:text-rose-800 transition">پاک کردن</button>
                <button type="button" @click="applyAndClose()" class="rounded-lg bg-[#010619] px-3.5 py-1.5 text-xs font-bold text-[#ffde5b] hover:bg-[#091333] transition shadow-xs">
                    تأیید
                </button>
            </div>
        </div>
    </div>
</div>