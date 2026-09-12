<div>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت طرح‌ها</h1>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.cate-designs') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">
                دسته‌بندی طرح‌ها
            </a>
            <a href="{{ route('admin.designs.create') }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
                + طرح جدید
            </a>
        </div>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام طرح..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">نام</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">اسلاگ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">دسته‌بندی</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تصاویر</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($designs as $design)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $design->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $design->name }}</td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs" dir="ltr">{{ $design->slug }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $design->category?->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $design->images_count }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $design->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $design->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.designs.edit', $design->id) }}" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</a>
                            <button wire:click="delete({{ $design->id }})" wire:confirm="آیا از حذف این طرح مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">طرحی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $designs->links() }}</div>
    </div>
</div>