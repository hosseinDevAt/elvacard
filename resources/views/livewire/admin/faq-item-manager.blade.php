<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
            + ایجاد سوال
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش سوال' : 'سوال جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="admin-label">سوال</label>
                    <input type="text" wire:model="question" class="admin-input">
                    @error('question') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">پاسخ</label>
                    <textarea wire:model="answer" rows="5" class="admin-input"></textarea>
                    @error('answer') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-40">
                        <label class="admin-label">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="admin-input">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
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
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در سوال یا پاسخ..." class="admin-input sm:w-80">
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">سوال</th>
                    <th class="admin-th">پاسخ</th>
                    <th class="admin-th">ترتیب</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($faqs as $faq)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $faq->id }}</td>
                        <td class="admin-td font-medium">{{ $faq->question }}</td>
                        <td class="admin-td text-gray-500 max-w-xs">{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 80) }}</td>
                        <td class="admin-td text-gray-500">{{ $faq->sort_order }}</td>
                        <td class="admin-td">
                            <span class="{{ $faq->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $faq->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="admin-td">
                            <button wire:click="edit({{ $faq->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $faq->id }})" wire:confirm="آیا از حذف این سوال مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-empty">سوالی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $faqs->links() }}</div>
    </div>
</div>