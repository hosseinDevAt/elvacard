<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
            + مقاله جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش مقاله' : 'مقاله جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="admin-label">عنوان</label>
                    <input type="text" wire:model="title" class="admin-input">
                    @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">دسته‌بندی</label>
                    <select wire:model="articleCategoryId" class="admin-input">
                        <option value="">بدون دسته‌بندی</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('articleCategoryId') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">خلاصه</label>
                    <textarea wire:model="excerpt" rows="3" class="admin-input"></textarea>
                    @error('excerpt') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">محتوا</label>
                    <textarea wire:model="content" rows="8" class="admin-input"></textarea>
                    @error('content') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">تصویر شاخص</label>
                    <input type="file" wire:model="coverImageUpload" accept="image/*" class="w-full text-sm text-gray-600 file:me-3 file:rounded-lg file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-gray-700">
                    @error('coverImageUpload') <p class="admin-error">{{ $message }}</p> @enderror
                    @if ($coverImageUpload)
                        <img src="{{ $coverImageUpload->temporaryUrl() }}" alt="پیش‌نمایش تصویر شاخص" class="mt-3 h-40 w-full object-cover rounded-lg border border-gray-100">
                    @elseif ($coverImage)
                        <img src="{{ asset('storage/' . $coverImage) }}" alt="تصویر شاخص فعلی" class="mt-3 h-40 w-full object-cover rounded-lg border border-gray-100">
                    @endif
                    @if ($coverImage && ! $coverImageUpload)
                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                            <input type="checkbox" wire:model="removeCoverImage" class="rounded border-gray-300 text-red-500"> حذف تصویر شاخص
                        </label>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">وضعیت</label>
                        <select wire:model="status" class="admin-input">
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

                <div class="border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-900 mb-3">سئو</h4>
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
                            <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-gray-300 text-yellow-500">
                            <label for="robots_index" class="text-sm text-gray-700">قابل ایندکس در موتورهای جستجو</label>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا محتوا..." class="admin-input sm:w-80">
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">عنوان</th>
                    <th class="admin-th">دسته‌بندی</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">تاریخ انتشار</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($articles as $article)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $article->id }}</td>
                        <td class="admin-td font-medium">{{ $article->title }}</td>
                        <td class="admin-td text-gray-500">{{ $article->category?->name ?? 'بدون دسته‌بندی' }}</td>
                        <td class="admin-td">
                            <span class="{{ $article->status === \App\Enums\ArticleStatusEnum::PUBLISHED ? 'text-green-600' : 'text-red-500' }}">
                                {{ $article->status->faLabel() }}
                            </span>
                        </td>
                        <td class="admin-td text-gray-500 text-xs">{{ $article->published_at ? jalali_date($article->published_at, 'datetime') : '—' }}</td>
                        <td class="admin-td">
                            <button wire:click="edit({{ $article->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $article->id }})" wire:confirm="آیا از حذف این مقاله مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-empty">مقاله‌ای یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $articles->links() }}</div>
    </div>
</div>