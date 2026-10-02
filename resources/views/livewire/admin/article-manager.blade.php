<div>
    <x-admin.page-header title="مدیریت مقالات" subtitle="ایجاد و انتشار مقالات بلاگ، راهنماها و اخبار فروشگاه">
        <x-slot:actions>
            <button wire:click="$set('showForm', true)" type="button" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-indigo-600/20">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>مقاله جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش مقاله' : 'مقاله جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="admin-label">عنوان مقاله</label>
                    <input type="text" wire:model="title" class="admin-input">
                    @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">دسته‌بندی</label>
                    <select wire:model="articleCategoryId" class="admin-select">
                        <option value="">بدون دسته‌بندی</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('articleCategoryId') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">خلاصه (چکیده)</label>
                    <textarea wire:model="excerpt" rows="3" class="admin-input"></textarea>
                    @error('excerpt') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">محتوای کامل مقاله</label>
                    <textarea wire:model="content" rows="8" class="admin-input"></textarea>
                    @error('content') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">تصویر شاخص</label>
                    <input type="file" wire:model="coverImageUpload" accept="image/*" class="w-full text-xs text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
                    @error('coverImageUpload') <p class="admin-error">{{ $message }}</p> @enderror
                    @if ($coverImageUpload)
                        <img src="{{ $coverImageUpload->temporaryUrl() }}" alt="پیش‌نمایش تصویر شاخص" class="mt-3 h-40 w-full object-cover rounded-xl border border-slate-200">
                    @elseif ($coverImage)
                        <img src="{{ asset('storage/' . $coverImage) }}" alt="تصویر شاخص فعلی" class="mt-3 h-40 w-full object-cover rounded-xl border border-slate-200">
                    @endif
                    @if ($coverImage && ! $coverImageUpload)
                        <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-rose-600 cursor-pointer">
                            <input type="checkbox" wire:model="removeCoverImage" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            <span>حذف تصویر شاخص</span>
                        </label>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">وضعیت انتشار</label>
                        <select wire:model="status" class="admin-select">
                            @foreach(\App\Enums\ArticleStatusEnum::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->faLabel() }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">تاریخ انتشار (اختیاری)</label>
                        <x-jalali-date-input mode="datetime" wire:model="publishedAt" id="article_published_at" />
                        @error('publishedAt') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <h4 class="font-bold text-slate-900 mb-3">سئو و متادیتا</h4>
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
                            <input type="text" wire:model="canonicalUrl" placeholder="https://example.com/..." dir="ltr" class="admin-input">
                            @error('canonicalUrl') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="robots_index" class="text-xs font-semibold text-slate-700">قابل ایندکس در موتورهای جستجو</label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">{{ $editingId ? 'ذخیره تغییرات' : 'ایجاد مقاله' }}</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">لیست کل مقالات</h3>
            <div class="w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا محتوا..." class="admin-input py-2 text-xs">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">عنوان</th>
                        <th class="admin-th">دسته‌بندی</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th">تاریخ انتشار</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($articles as $article)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $article->id }}</td>
                            <td class="admin-td font-bold text-slate-900">{{ $article->title }}</td>
                            <td class="admin-td text-slate-600 text-xs">{{ $article->category?->name ?? 'بدون دسته‌بندی' }}</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $article->status === \App\Enums\ArticleStatusEnum::PUBLISHED ? 'admin-badge-success' : 'admin-badge-neutral' }}">
                                    {{ $article->status->faLabel() }}
                                </span>
                            </td>
                            <td class="admin-td text-slate-400 text-xs">{{ $article->published_at ? jalali_date($article->published_at, 'datetime') : '—' }}</td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="edit({{ $article->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button wire:click="delete({{ $article->id }})" wire:confirm="آیا از حذف این مقاله مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8">
                                <x-admin.empty-state title="مقاله‌ای یافت نشد" description="هیچ مقاله‌ای با عبارت جستجوی وارد شده پیدا نشد." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $articles->links() }}</div>
    </div>
</div>