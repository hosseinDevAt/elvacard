@extends('layouts.admin')

@section('title', 'مدیریت سفارشات')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">مدیریت سفارشات</h1>
    <div class="flex gap-2">
        <button wire:click="$set('statusFilter', null)" class="px-3 py-1.5 rounded-lg text-xs transition {{ !$statusFilter ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">همه</button>
        <button wire:click="$set('statusFilter', 'pending')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">در انتظار</button>
        <button wire:click="$set('statusFilter', 'paid')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'paid' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">پرداخت شده</button>
        <button wire:click="$set('statusFilter', 'processing')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}>در حال پردازش</button>
        <button wire:click="$set('statusFilter', 'shipped')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'shipped' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}>ارسال شده</button>
        <button wire:click="$set('statusFilter', 'completed')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'completed' ? 'bg-green-800 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}>تکمیل شده</button>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-right font-medium text-gray-500">#</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">کاربر</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">مبلغ</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">وضعیت</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">تاریخ</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">عملیات</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500">{{ $order->id }}</td>
                    <td class="px-4 py-3">{{ $order->user->name ?? '-' }} <span class="text-gray-400 text-xs">({{ $order->user->phone ?? '-' }})</span></td>
                    <td class="px-4 py-3 font-mono" dir="ltr">{{ number_format($order->total_price) }} تومان</td>
                    <td class="px-4 py-3">
                        @php
                            $statusColors = [
                                'pending' => 'bg-amber-100 text-amber-700',
                                'paid' => 'bg-green-100 text-green-700',
                                'processing' => 'bg-blue-100 text-blue-700',
                                'shipped' => 'bg-purple-100 text-purple-700',
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-700',
                            ];
                            $statusLabels = [
                                'pending' => 'در انتظار',
                                'paid' => 'پرداخت شده',
                                'processing' => 'در حال پردازش',
                                'shipped' => 'ارسال شده',
                                'completed' => 'تکمیل شده',
                                'cancelled' => 'لغو شده',
                            ];
                        @endphp
                        <span class="{{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }} text-xs px-2 py-1 rounded-full">
                            {{ $statusLabels[$order->status] ?? $order->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3">
                        <select wire:change="updateStatus({{ $order->id }}, $event.target.value)"
                            class="text-xs border border-gray-300 rounded-lg px-2 py-1 focus:border-yellow-500">
                            @foreach(['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'] as $s)
                                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>
                                    {{ $statusLabels[$s] }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">سفارشی وجود ندارد</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $orders->links() }}</div>
</div>
@endsection
