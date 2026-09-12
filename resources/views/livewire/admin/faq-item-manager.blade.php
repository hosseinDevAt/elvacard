<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت سوالات متداول</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + ایجاد سوال
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش سوال' : 'سوال جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سوال</label>
                    <input type="text" wire:model="question" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('question') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">پاسخ</label>
                    <textarea wire:model="answer" rows="5" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                    @error('answer') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('sortOrder') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-2 pb-1">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                        <label for="is_active" class="text-sm text-gray-700">فعال</label>
                    </div>
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در سوال یا پاسخ..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">سوال</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">پاسخ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">ترتیب</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($faqs as $faq)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $faq->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $faq->question }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-xs">{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 80) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $faq->sort_order }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $faq->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $faq->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="edit({{ $faq->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $faq->id }})" wire:confirm="آیا از حذف این سوال مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">سوالی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $faqs->links() }}</div>
    </div>
</div>