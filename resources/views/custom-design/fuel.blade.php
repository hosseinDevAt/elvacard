@extends('layouts.app')

@section('meta')
    <title>طراحی کارت سوخت - {{ site_setting('site_name', config('app.name')) }}</title>
    <meta name="description" content="سرویس طراحی کارت سوخت اختصاصی به‌زودی فعال می‌شود.">
    <link rel="canonical" href="{{ route('custom-card.fuel') }}">
    <meta name="robots" content="noindex,follow">
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('custom-card.design') }}" wire:navigate
               class="inline-flex items-center text-sm text-primary-600 hover:text-primary-800 transition">
                <x-icons.arrow-left class="ms-1" />
                بازگشت به طراحی کارت اختصاصی
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">طراحی کارت سوخت</h1>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm leading-relaxed text-amber-800">
            <p class="font-semibold">سرویس طراحی کارت سوخت به‌زودی فعال می‌شود.</p>
            <p class="mt-2">این بخش پس از راه‌اندازی، امکان طراحی کارت سوخت اختصاصی را فراهم می‌کند.</p>
            <a href="{{ route('custom-card.design') }}" wire:navigate
               class="mt-4 inline-flex items-center text-sm font-semibold text-primary-600 hover:text-primary-800 transition">
                بازگشت به انتخاب نوع کارت
                <x-icons.arrow-left class="me-0 ms-2 h-4 w-4" />
            </a>
        </div>
    </div>
@endsection