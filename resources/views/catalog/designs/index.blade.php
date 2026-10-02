@extends('layouts.app')

@section('title', 'فهرست طرح‌ها - الواکارت')

@section('content')
    <div class="min-h-[70vh] bg-slate-50/50 py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-slate-200/80 pb-6">
                <div>
                    <h1 class="text-2xl font-black text-[#010619] sm:text-3xl">فهرست طرح‌ها</h1>
                    <p class="mt-2 text-sm text-slate-600">دسته‌بندی‌ها، طرح‌ها و تصاویر فعال قابل حکاکی لیزری را مشاهده کنید.</p>
                </div>

                {{-- Active Filters Badges --}}
                @if($selectedCategory || $selectedColorId)
                    <div class="flex flex-wrap items-center gap-2">
                        @if($selectedCategory)
                            <span class="inline-flex items-center gap-1.5 rounded-xl bg-[#010619] px-3.5 py-1.5 text-xs font-bold text-[#ffde5b] shadow-xs">
                                <span class="text-slate-400 font-normal">دسته:</span>
                                <span>{{ $selectedCategoryName ?: $selectedCategory }}</span>
                            </span>
                        @endif

                        @if($selectedColorId)
                            <span class="inline-flex items-center gap-1.5 rounded-xl bg-slate-200/80 px-3.5 py-1.5 text-xs font-bold text-slate-800 shadow-xs">
                                <span class="text-slate-500 font-normal">شناسه رنگ:</span>
                                <span class="font-mono">{{ $selectedColorId }}</span>
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Design Grid Partial --}}
            @include('catalog.partials.design-grid', ['catalog' => $catalog])
        </div>
    </div>
@endsection