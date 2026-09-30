@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-900">پیگیری سفارش</h1>
            <p class="mt-2 text-sm text-gray-600">
                برای پیگیری سفارش، کد پیگیری که پس از ثبت سفارش به شما نمایش داده شد را وارد کنید.
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
                    <label for="token" class="mb-1 block text-sm font-medium text-gray-700">کد پیگیری سفارش</label>
                    <input
                        id="token"
                        name="token"
                        type="text"
                        value="{{ old('token') }}"
                        required
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="کد پیگیری"
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