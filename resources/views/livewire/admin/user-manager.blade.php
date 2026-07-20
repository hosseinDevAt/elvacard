@extends('layouts.admin')

@section('title', 'مدیریت کاربران')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">مدیریت کاربران</h1>
    <div class="w-72">
        <input type="text" wire:model.live="search" placeholder="جستجو بر اساس نام یا شماره..."
            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-right font-medium text-gray-500">#</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">نام</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">تلفن</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">آدرس</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">تعداد سفارش</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">تاریخ عضویت</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500">{{ $user->id }}</td>
                    <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-3 font-mono" dir="ltr">{{ $user->phone }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs max-w-[200px] truncate">{{ $user->address ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $user->orders_count }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $user->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">کاربری یافت نشد</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $users->links() }}</div>
</div>
@endsection
