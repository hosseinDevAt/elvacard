@extends('layouts.app')

@section('title', 'خانه - کارت شخصی')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="text-center mb-8 sm:mb-12">
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-3 sm:mb-4">کارت بانکی و سوخت خودت رو طراحی کن</h1>
        <p class="text-sm sm:text-lg text-gray-600 max-w-2xl mx-auto">
            با انتخاب طرح و رنگ دلخواه، کارت فلزی بانکی یا کارت سوخت شخصی‌سازی شده داشته باش
        </p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 sm:gap-8 max-w-4xl mx-auto">
        <a href="{{ route('designer.bank') }}" class="group block bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
            <div class="aspect-[1.58/1] bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-12 h-12 sm:w-16 sm:h-16 text-white/80 mx-auto mb-2 sm:mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                    <span class="text-white/90 text-base sm:text-lg font-semibold">کارت بانکی فلزی</span>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                <h2 class="text-base sm:text-lg font-bold text-gray-900 mb-1 sm:mb-2">کارت بانکی فلزی</h2>
                <p class="text-xs sm:text-sm text-gray-500">طراحی اختصاصی کارت بانکی فلزی با طرح‌های متنوع</p>
            </div>
        </a>

        <a href="{{ route('designer.fuel') }}" class="group block bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
            <div class="aspect-[1.58/1] bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-12 h-12 sm:w-16 sm:h-16 text-white/80 mx-auto mb-2 sm:mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <span class="text-white/90 text-base sm:text-lg font-semibold">کارت سوخت</span>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                <h2 class="text-base sm:text-lg font-bold text-gray-900 mb-1 sm:mb-2">کارت سوخت شخصی</h2>
                <p class="text-xs sm:text-sm text-gray-500">کارت سوخت خود را با طرح دلخواه شخصی‌سازی کنید</p>
            </div>
        </a>
    </div>
</div>
@endsection
