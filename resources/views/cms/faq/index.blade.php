@extends('layouts.app')

@section('meta')
    <title>سوالات متداول - {{ config('app.name') }}</title>
    <meta name="description" content="پاسخ‌های رایج سوالات مشتریان {{ config('app.name') }}">
@endsection

@section('content')
<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <header class="mb-10 text-center">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">سوالات متداول</h1>
        <p class="text-gray-600">پاسخ‌های سریع به سوالات رایج</p>
    </header>

    <div class="space-y-4">
        @forelse ($faqs as $faq)
            @include('components.faq-item', ['faq' => $faq])
        @empty
            <div class="text-center py-12 text-gray-500">
                سوال متداولی ثبت نشده است.
            </div>
        @endforelse
    </div>
</main>
@endsection