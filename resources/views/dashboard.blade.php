@extends('layouts.app')

@section('title', 'پنل کاربری - کارت شخصی')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">سلام {{ Auth::user()->name }} 👋</h1>
            <p class="text-gray-500 mt-1">به پنل کاربری خود خوش آمدید</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 border border-gray-300 px-4 py-2 rounded-lg transition">
                خروج
            </button>
        </form>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="font-bold text-gray-900 mb-2">اطلاعات حساب</h3>
            <div class="space-y-2 text-sm text-gray-600">
                <p><span class="text-gray-400">نام:</span> {{ Auth::user()->name }}</p>
                <p><span class="text-gray-400">تلفن:</span> {{ Auth::user()->phone }}</p>
                @if(Auth::user()->address)
                    <p><span class="text-gray-400">آدرس:</span> {{ Auth::user()->address }}</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="font-bold text-gray-900 mb-2">سفارشات</h3>
            <p class="text-sm text-gray-500">شما {{ Auth::user()->orders->count() }} سفارش دارید</p>
        </div>

        <a href="{{ route('designer.bank') }}" class="bg-yellow-500 text-white rounded-2xl p-6 hover:bg-yellow-600 transition flex items-center justify-center">
            <div class="text-center">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="font-bold">طراحی کارت جدید</span>
            </div>
        </a>
    </div>
</div>
@endsection
