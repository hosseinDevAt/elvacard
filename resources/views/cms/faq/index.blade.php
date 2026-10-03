@extends('layouts.app')

@section('title')سوالات متداول - {{ site_setting('site_name', config('app.name')) }}@endsection

@section('meta')
    <meta name="description" content="پاسخ‌های رایج سوالات مشتریان {{ site_setting('site_name', config('app.name')) }}">
@endsection

@section('content')
<main class="min-h-[70vh] bg-slate-50/50 py-12 sm:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-breadcrumbs :items="[
            ['label' => 'سوالات متداول']
        ]" />

        {{-- Header --}}
        <header class="mb-10 text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ffde5b]/20 text-[#664d00] border border-[#ffde5b]/60 px-3.5 py-1 text-xs font-bold mb-3">
                <span>مرکز پاسخگویی</span>
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-[#010619] tracking-tight mb-2">سوالات متداول</h1>
            <p class="text-sm text-slate-600">پاسخ‌های سریع و کامل به پرتکرارترین پرسش‌های کاربران الواکارت</p>
        </header>

        {{-- Accordion List --}}
        <div class="space-y-4">
            @forelse ($faqs as $faq)
                @include('components.faq-item', ['faq' => $faq])
            @empty
                <div class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-xs">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <x-icons.lifebuoy class="h-7 w-7" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-[#010619]">سوال متداولی ثبت نشده است</h3>
                    <p class="mt-1 text-xs text-slate-500">در صورت داشتن هرگونه سوال، با تیم پشتیبانی ما تماس حاصل فرمایید.</p>
                </div>
            @endforelse
        </div>
    </div>
</main>
@endsection