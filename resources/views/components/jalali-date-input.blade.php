@php
    $wireModel = $attributes->wire('model');
    $modelName = $wireModel->value() ?? 'jalali-date';
    $inputId = $attributes->get('id') ?? 'jalali_' . mb_substr(md5($modelName), 0, 12);
    $mode = $mode ?? 'date';
    $inputAttributes = $attributes->except(['class', 'id', 'name']);
    $classes = trim('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-200 ' . ($attributes->get('class') ?? ''));
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
            class="absolute inset-y-0 left-0 flex items-center px-3 text-gray-400 hover:text-yellow-600 transition"
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
        class="absolute z-30 mt-1 w-72 rounded-xl border border-gray-200 bg-white p-3 shadow-xl"
        style="display: none;"
    >
        {{-- Header nav --}}
        <div class="flex items-center justify-between mb-2">
            <button type="button" @click="prevMonth()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition font-bold">»</button>
            <div class="text-sm font-bold text-gray-900">
                <span x-text="monthNames[viewMonth]"></span>
                <span x-text="toPd(viewYear)"></span>
            </div>
            <button type="button" @click="nextMonth()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition font-bold">«</button>
        </div>

        {{-- Weekdays header (Saturday-first) --}}
        <div class="grid grid-cols-7 mb-1">
            <template x-for="d in weekDays" :key="d">
                <div class="h-8 flex items-center justify-center text-[10px] font-bold text-gray-400" x-text="d"></div>
            </template>
        </div>

        {{-- Day grid --}}
        <div class="grid grid-cols-7 gap-0.5">
            <template x-for="(cell, index) in grid" :key="index">
                <button
                    type="button"
                    x-show="cell"
                    :disabled="cell.disabled"
                    x-on:click="cell && !cell.disabled && pickDay(viewYear, viewMonth, cell.day)"
                    class="h-8 rounded-lg text-xs font-medium transition disabled:opacity-30 disabled:cursor-not-allowed"
                    :class="cell.selected
                        ? 'bg-amber-500 text-white shadow'
                        : (cell.isToday ? 'ring-1 ring-amber-400 text-amber-700 font-bold hover:bg-amber-50' : 'hover:bg-gray-100 text-gray-800')"
                    x-text="cell ? toPd(cell.day) : ''"
                ></button>
            </template>
        </div>

        {{-- Time picker (datetime mode) --}}
        <div x-show="mode === 'datetime' && selected" class="mt-3 flex items-end justify-center gap-2 border-t border-gray-100 pt-3" x-cloak>
            <div class="flex flex-col items-center">
                <label class="text-[10px] text-gray-400 mb-1">ساعت</label>
                <select x-model.number="hours" @change="refreshTime()" class="rounded-md border border-gray-300 text-xs px-2 py-1 text-gray-800">
                    <template x-for="h in 24" :key="h">
                        <option :value="h - 1" x-text="toPd(String(h - 1).padStart(2, '0'))"></option>
                    </template>
                </select>
            </div>
            <div class="flex flex-col items-center">
                <label class="text-[10px] text-gray-400 mb-1">دقیقه</label>
                <select x-model.number="minutes" @change="refreshTime()" class="rounded-md border border-gray-300 text-xs px-2 py-1 text-gray-800">
                    <template x-for="m in 60" :key="m">
                        <option :value="m - 1" x-text="toPd(String(m - 1).padStart(2, '0'))"></option>
                    </template>
                </select>
            </div>
        </div>

        {{-- Footer actions --}}
        <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2">
            <button type="button" @click="gotoToday()" class="text-xs font-medium text-yellow-700 hover:text-yellow-900 transition">امروز</button>
            <div class="flex items-center gap-2">
                <button type="button" @click="clearValue()" class="text-xs font-medium text-red-600 hover:text-red-800 transition">پاک کردن</button>
                <button type="button" @click="applyAndClose()" class="rounded-lg bg-gray-800 px-3 py-1 text-xs font-semibold text-white hover:bg-gray-900 transition">
                    تأیید
                </button>
            </div>
        </div>
    </div>
</div>