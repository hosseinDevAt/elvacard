@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">فهرست طرح‌ها</h1>
            <p class="mt-1 text-sm text-gray-600">دسته‌بندی‌ها، طرح‌ها و تصاویر فعال طرح‌ها را مشاهده کنید.</p>

            @if($selectedCategory)
                <p class="mt-2 text-sm text-primary-700">
                    فیلتر دسته‌بندی فعال: {{ $selectedCategory }}
                </p>
            @endif

            @if($selectedColorId)
                <p class="mt-1 text-sm text-primary-700">
                    فیلتر سازگاری (شناسه رنگ): {{ $selectedColorId }}
                </p>
            @endif
        </div>

        @include('catalog.partials.design-grid', ['catalog' => $catalog])
    </div>
@endsection