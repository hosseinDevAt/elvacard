@extends('layouts.admin')

@section('title', 'مدیریت انواع کارت')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">مدیریت انواع کارت</h1>
    <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
        + نوع کارت جدید
    </button>
</div>

@if($showForm)
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش نوع کارت' : 'نوع کارت جدید' }}</h3>
        <form wire:submit="save" class="grid grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نوع</label>
                <select wire:model="type" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 transition">
                    <option value="bank">کارت بانکی</option>
                    <option value="fuel">کارت سوخت</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">رنگ</label>
                <select wire:model="colorId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 transition">
                    <option value="">انتخاب رنگ</option>
                    @foreach($colors as $color)
                        <option value="{{ $color->id }}">{{ $color->name }}</option>
                    @endforeach
                </select>
                @error('colorId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">قیمت پایه (تومان)</label>
                <input type="number" wire:model="basePrice" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 transition" dir="ltr">
            </div>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isAvailable" class="rounded border-gray-300 text-yellow-500">
                    <span class="text-sm text-gray-700">فعال</span>
                </label>
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">لغو</button>
            </div>
        </form>
    </div>
@endif

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-right font-medium text-gray-500">#</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">نوع</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">رنگ</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">قیمت پایه</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">وضعیت</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">عملیات</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($cardTypes as $ct)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500">{{ $ct->id }}</td>
                    <td class="px-4 py-3">
                        <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">{{ $ct->type === 'bank' ? 'بانکی' : 'سوخت' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded border" style="background-color: {{ $ct->color->color_code }}"></div>
                            <span>{{ $ct->color->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono" dir="ltr">{{ number_format($ct->base_price) }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ $ct->is_available ? 'text-green-600' : 'text-red-500' }} text-xs">
                            {{ $ct->is_available ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <button wire:click="edit({{ $ct->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs ml-2">ویرایش</button>
                        <button wire:click="delete({{ $ct->id }})" wire:confirm="آیا مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">نوع کارتی وجود ندارد</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $cardTypes->links() }}</div>
</div>
@endsection
