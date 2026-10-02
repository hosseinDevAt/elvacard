<div>
    <x-admin.page-header
        title="مدیریت سوالات متداول"
        description="افزودن، ویرایش و مدیریت پرسش‌ها و پاسخ‌های متداول کاربران"
    >
        <x-slot:actions>
            <button type="button" wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>ایجاد سوال جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش سوال' : 'سوال جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="admin-label">سوال</label>
                    <input type="text" wire:model="question" class="admin-input" placeholder="عنوان یا متن پرسش">
                    @error('question') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">پاسخ</label>
                    <textarea wire:model="answer" rows="5" class="admin-input" placeholder="متن کامل پاسخ به پرسش"></textarea>
                    @error('answer') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-40">
                        <label class="admin-label">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="admin-input">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-2 pb-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                        <label for="is_active" class="text-sm font-medium text-slate-700">فعال</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <span>ذخیره</span>
                        </button>
                        <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary">
                            <span>لغو</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <div class="relative sm:w-80">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در سوال یا پاسخ..." class="admin-input ps-9">
            <svg class="w-4 h-4 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">سوال</th>
                        <th class="admin-th">پاسخ</th>
                        <th class="admin-th">ترتیب</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($faqs as $faq)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-slate-400 font-mono text-xs">{{ $faq->id }}</td>
                            <td class="admin-td font-medium text-slate-900">{{ $faq->question }}</td>
                            <td class="admin-td text-slate-500 max-w-xs">{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 80) }}</td>
                            <td class="admin-td text-slate-500 font-mono text-xs">{{ $faq->sort_order }}</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $faq->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                                    {{ $faq->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" wire:click="edit({{ $faq->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button type="button" wire:click="delete({{ $faq->id }})" wire:confirm="آیا از حذف این سوال مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8">
                                <x-admin.empty-state
                                    title="سوالی یافت نشد"
                                    description="هیچ سوال متداولی با معیارهای جستجو یا در سامانه یافت نشد."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($faqs->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $faqs->links() }}</div>
        @endif
    </div>
</div>