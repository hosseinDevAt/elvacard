<div>
    <x-admin.page-header
        title="مدیریت صفحات"
        description="ایجاد، ویرایش و مدیریت صفحات ایستا، متنی، راهنماها و قوانین سایت"
    >
        <x-slot:actions>
            <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>صفحه جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش صفحه' : 'صفحه جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">عنوان</label>
                        <input type="text" wire:model="title" class="admin-input" placeholder="مثال: شرایط و قوانین">
                        @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">نوع صفحه</label>
                        <input type="text" wire:model="pageType" dir="ltr" placeholder="general" class="admin-input font-mono text-xs">
                        @error('pageType') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="admin-label">محتوا</label>
                    <textarea wire:model="content" rows="12" class="admin-input" placeholder="محتوای متنی یا HTML صفحه"></textarea>
                    @error('content') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">تصویر شاخص</label>
                    <input type="file" wire:model="imageUpload" accept="image/*" class="w-full text-sm text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-700 transition file:cursor-pointer">
                    @error('imageUpload') <p class="admin-error">{{ $message }}</p> @enderror
                    @if ($imageUpload)
                        <img src="{{ $imageUpload->temporaryUrl() }}" alt="پیش‌نمایش تصویر" class="mt-3 h-32 w-full object-cover rounded-xl border border-slate-200">
                    @elseif ($imagePath)
                        <img src="{{ asset('storage/' . $imagePath) }}" alt="تصویر فعلی" class="mt-3 h-32 w-full object-cover rounded-xl border border-slate-200">
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-sm font-medium text-slate-700">فعال (نمایش عمومی)</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="robots_index" class="text-sm font-medium text-slate-700">قابل ایندکس در موتورهای جستجو</label>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <h4 class="font-bold text-slate-900 mb-3 text-sm">تنظیمات سئو</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="admin-label">عنوان سئو</label>
                            <input type="text" wire:model="metaTitle" class="admin-input">
                            @error('metaTitle') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">توضیحات سئو</label>
                            <textarea wire:model="metaDescription" rows="3" class="admin-input"></textarea>
                            @error('metaDescription') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">آدرس Canonical</label>
                            <input type="text" wire:model="canonicalUrl" placeholder="https://example.com/..." dir="ltr" class="admin-input font-mono text-xs">
                            @error('canonicalUrl') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
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
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا اسلاگ..." class="admin-input ps-9">
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
                        <th class="admin-th">عنوان</th>
                        <th class="admin-th">اسلاگ</th>
                        <th class="admin-th">نوع</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pages as $page)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-slate-400 font-mono text-xs">{{ $page->id }}</td>
                            <td class="admin-td font-medium text-slate-900">{{ $page->title }}</td>
                            <td class="admin-td text-slate-500 font-mono text-xs" dir="ltr">{{ $page->slug }}</td>
                            <td class="admin-td text-slate-500 text-xs" dir="ltr">{{ $page->page_type }}</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $page->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                                    {{ $page->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="edit({{ $page->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button wire:click="delete({{ $page->id }})" wire:confirm="آیا از حذف این صفحه مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8">
                                <x-admin.empty-state
                                    title="صفحه‌ای یافت نشد"
                                    description="هنوز صفحه‌ای ثبت نشده یا نتیجه‌ای با عبارت جستجو مطابقت ندارد."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pages->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $pages->links() }}</div>
        @endif
    </div>
</div>