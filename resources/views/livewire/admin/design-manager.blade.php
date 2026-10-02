<div>
    <x-admin.page-header
        title="طرح‌های فعال در میزکار"
        description="مدیریت، بارگذاری و تنظیم طرح‌های چاپ و شخصی‌سازی کارت"
    >
        <x-slot:actions>
            <a href="{{ route('admin.cate-designs') }}" class="admin-btn admin-btn-secondary">
                <span>دسته‌بندی طرح‌ها</span>
            </a>
            <a href="{{ route('admin.designs.create') }}" class="admin-btn admin-btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>افزودن طرح جدید</span>
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-card overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">لیست کلی طرح‌های کارت</h3>
            <div class="relative w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام طرح..." class="admin-input ps-9 py-2 text-xs">
                <svg class="w-4 h-4 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">نام طرح</th>
                        <th class="admin-th">اسلاگ</th>
                        <th class="admin-th">دسته‌بندی</th>
                        <th class="admin-th">تصاویر</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($designs as $design)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $design->id }}</td>
                            <td class="admin-td">
                                <span class="font-bold text-slate-900 block text-xs">{{ $design->name }}</span>
                            </td>
                            <td class="admin-td text-slate-400 font-mono text-xs" dir="ltr">{{ $design->slug }}</td>
                            <td class="admin-td">
                                <span class="text-xs text-slate-600">{{ $design->category?->name ?? '-' }}</span>
                            </td>
                            <td class="admin-td">
                                <span class="admin-badge admin-badge-neutral text-xs">{{ $design->images_count }} تصویر</span>
                            </td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $design->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                                    {{ $design->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                                @if($design->is_active)
                                    @if($workspaceReady[$design->id] ?? false)
                                        <span class="block text-[10px] text-emerald-600 font-medium mt-0.5">قابل نمایش در میزکار</span>
                                    @else
                                        <span class="block text-[10px] text-amber-600 font-medium mt-0.5">پنهان از میزکار</span>
                                    @endif
                                @endif
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.designs.edit', $design->id) }}" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">
                                        ویرایش
                                    </a>
                                    <button wire:click="delete({{ $design->id }})" wire:confirm="آیا از حذف این طرح مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">
                                        حذف
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8">
                                <x-admin.empty-state
                                    title="طرحی یافت نشد"
                                    description="هنوز هیچ طرحی ثبت نشده یا نتیجه‌ای با عبارت جستجو مطابقت ندارد."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($designs->hasPages())
            <div class="p-5 border-t border-slate-100">{{ $designs->links() }}</div>
        @endif
    </div>
</div>