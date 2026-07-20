@extends('layouts.admin')

@section('title', 'مدیریت دسته‌بندی طرح‌ها')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">مدیریت دسته‌بندی طرح‌ها</h1>
    <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
        + دسته‌بندی جدید
    </button>
</div>

@if($showForm)
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}</h3>
        <form wire:submit="save" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">نام</label>
                <input type="text" wire:model="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-2 pb-1">
                <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                <label for="is_active" class="text-sm text-gray-700">فعال</label>
            </div>
            <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
            <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
        </form>
    </div>
@endif

<div class="space-y-4">
    @forelse($categories as $category)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">{{ $category->name }}</h3>
                    <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                        <span class="{{ $category->is_active ? 'text-green-600' : 'text-red-500' }}">
                            {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                        <span>{{ $category->groupDesigns->count() }} گروه طرح</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button wire:click="edit({{ $category->id }})" class="text-yellow-500 hover:text-yellow-700 text-sm">ویرایش</button>
                    <button wire:click="delete({{ $category->id }})" wire:confirm="آیا مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-sm">حذف</button>
                </div>
            </div>
            @if($category->groupDesigns->count())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($category->groupDesigns as $group)
                        <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">{{ $group->name }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">دسته‌بندی‌ای وجود ندارد</div>
    @endforelse
</div>

<div class="mt-4">{{ $categories->links() }}</div>
@endsection
