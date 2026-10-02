<div>
    <x-admin.page-header
        title="مدیریت منوها"
        description="تعریف، سازماندهی و پیکربندی منوهای اصلی، هدر و فوتر سامانه"
    >
        <x-slot:actions>
            <button type="button" wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>منوی جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش منو' : 'منوی جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">نام منو</label>
                        <input type="text" wire:model="name" class="admin-input" placeholder="مثال: منوی اصلی هدر">
                        @error('name') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">موقعیت قرارگیری</label>
                        <select wire:model="location" class="admin-input">
                            <option value="header">هدر (بالای سایت)</option>
                            <option value="footer">فوتر (پایین سایت)</option>
                        </select>
                        @error('location') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <span>ذخیره</span>
                    </button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary">
                        <span>لغو</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <div class="relative sm:w-80">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام منو..." class="admin-input ps-9">
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
                        <th class="admin-th">نام منو</th>
                        <th class="admin-th">موقعیت</th>
                        <th class="admin-th">تعداد آیتم‌ها</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($menus as $menu)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-slate-400 font-mono text-xs">{{ $menu->id }}</td>
                            <td class="admin-td font-medium text-slate-900">{{ $menu->name }}</td>
                            <td class="admin-td text-slate-500 text-xs" dir="ltr">{{ $menu->location }}</td>
                            <td class="admin-td">
                                <span class="admin-badge admin-badge-neutral text-xs">{{ $menu->items_count }} آیتم</span>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" wire:click="edit({{ $menu->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button type="button" wire:click="delete({{ $menu->id }})" wire:confirm="آیا از حذف این منو مطمئن هستید؟ تمام آیتم‌های آن حذف خواهند شد." class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8">
                                <x-admin.empty-state
                                    title="منویی یافت نشد"
                                    description="هنوز هیچ منویی ثبت نشده است."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($menus->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $menus->links() }}</div>
        @endif
    </div>
</div>