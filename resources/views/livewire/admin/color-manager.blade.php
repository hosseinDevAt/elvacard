@extends('layouts.admin')

@section('title', 'مدیریت رنگ‌ها')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">مدیریت رنگ‌ها</h1>
    <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
        + رنگ جدید
    </button>
</div>

@if($showForm)
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش رنگ' : 'رنگ جدید' }}</h3>
        <form wire:submit="save" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">نام</label>
                <input type="text" wire:model="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="w-32">
                <label class="block text-sm font-medium text-gray-700 mb-1">کد رنگ</label>
                <input type="color" wire:model="colorCode" class="w-full h-10 rounded-lg border border-gray-300 cursor-pointer">
            </div>
            <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
            <button type="button" wire:click="$set('showForm', false); $wire.resetFields()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
        </form>
    </div>
@endif

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-right font-medium text-gray-500">#</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">نام</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">کد رنگ</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500">عملیات</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($colors as $color)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500">{{ $color->id }}</td>
                    <td class="px-4 py-3 font-medium">{{ $color->name }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded border" style="background-color: {{ $color->color_code }}"></div>
                            <span class="text-gray-500 font-mono text-xs">{{ $color->color_code }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <button wire:click="edit({{ $color->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs ml-2">ویرایش</button>
                        <button wire:click="delete({{ $color->id }})" wire:confirm="آیا از حذف این رنگ مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">رنگی وجود ندارد</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $colors->links() }}</div>
</div>
@endsection
