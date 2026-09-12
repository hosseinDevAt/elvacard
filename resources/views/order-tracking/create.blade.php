@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-900">پیگیری سفارش</h1>
            <p class="mt-2 text-sm text-gray-600">
                با وارد کردن شماره سفارش (مثل ORD-2026-000001) و شماره تماس ثبت‌شده، وضعیت سفارش خود را مشاهده کنید.
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('order-tracking.check') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="reference" class="mb-1 block text-sm font-medium text-gray-700">شماره سفارش</label>
                    <input
                        id="reference"
                        name="reference"
                        type="text"
                        value="{{ old('reference') }}"
                        required
                        placeholder="ORD-2026-000001"
                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                    />
                </div>

                <div>
                    <label for="customer_phone" class="mb-1 block text-sm font-medium text-gray-700">شماره تماس</label>
                    <input
                        id="customer_phone"
                        name="customer_phone"
                        type="text"
                        value="{{ old('customer_phone') }}"
                        required
                        placeholder="09123456789"
                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                    />
                </div>

                <button type="submit" class="w-full rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">
                    پیگیری سفارش
                </button>
            </form>
        </div>
    </div>
@endsection