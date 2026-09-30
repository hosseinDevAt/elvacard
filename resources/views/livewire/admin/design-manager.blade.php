<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">طرح‌های فعال در میزکار</h2>
            <p class="text-xs text-slate-500 mt-1">مدیریت، بارگذاری و تنظیم طرح‌های چاپ شخصی‌سازی کارت</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.cate-designs') }}" class="admin-btn admin-btn-secondary text-xs font-semibold">
                دسته‌بندی طرح‌ها
            </a>
            <a href="{{ route('admin.designs.create') }}" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-indigo-600/20">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>افزودن طرح جدید</span>
            </a>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">لیست کلی طرح‌های کارت</h3>
            <div class="w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام طرح..." class="admin-input py-2 text-xs">
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
                                <span class="admin-badge {{ $design->is_active ? 'admin-badge-success' : 'admin-badge-danger' }}">
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
                        <tr><td colspan="7" class="admin-empty">طرحی یافت نشد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $designs->links() }}</div>
    </div>
</div>