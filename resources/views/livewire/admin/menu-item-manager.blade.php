<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
            + آیتم منو جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش آیتم منو' : 'آیتم منو جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">منو</label>
                        <select wire:model="menuId" class="admin-input">
                            <option value="">انتخاب منو...</option>
                            @foreach($menus as $menu)
                                <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                            @endforeach
                        </select>
                        @error('menuId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">نوع آیتم</label>
                        <select wire:model="itemType" class="admin-input">
                            <option value="url">لینک خارجی</option>
                            <option value="page">صفحه</option>
                            <option value="product">محصول</option>
                            <option value="design">طرح</option>
                            <option value="article">مقاله</option>
                        </select>
                        @error('itemType') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="admin-label">عنوان</label>
                    <input type="text" wire:model="title" class="admin-input">
                    @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                @if($itemType === 'url')
                    <div>
                        <label class="admin-label">آدرس</label>
                        <input type="text" wire:model="customUrl" placeholder="https://example.com یا /pages/..." dir="ltr" class="admin-input">
                        @error('customUrl') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'page')
                    <div>
                        <label class="admin-label">صفحه</label>
                        <select wire:model="targetId" class="admin-input">
                            <option value="">انتخاب صفحه...</option>
                            @foreach($pages as $page)
                                <option value="{{ $page->id }}">{{ $page->title }} ({{ $page->slug }})</option>
                            @endforeach
                        </select>
                        @error('targetId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'product')
                    <div>
                        <label class="admin-label">محصول</label>
                        <select wire:model="targetId" class="admin-input">
                            <option value="">انتخاب محصول...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('targetId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'design')
                    <div>
                        <label class="admin-label">طرح</label>
                        <select wire:model="targetId" class="admin-input">
                            <option value="">انتخاب طرح...</option>
                            @foreach($designs as $design)
                                <option value="{{ $design->id }}">{{ $design->name }}</option>
                            @endforeach
                        </select>
                        @error('targetId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'article')
                    <div>
                        <label class="admin-label">مقاله</label>
                        <select wire:model="targetId" class="admin-input">
                            <option value="">انتخاب مقاله...</option>
                            @foreach($articles as $article)
                                <option value="{{ $article->id }}">{{ $article->title }}</option>
                            @endforeach
                        </select>
                        @error('targetId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="admin-label">ترتیب</label>
                        <input type="number" wire:model="sortOrder" class="admin-input">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">باز شدن در</label>
                        <select wire:model="target" class="admin-input">
                            <option value="_self">همان برگه</option>
                            <option value="_blank">برگه جدید</option>
                        </select>
                        @error('target') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                            <label for="is_active" class="text-sm text-gray-700">فعال</label>
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

    <div class="flex flex-wrap items-center gap-4 mb-4">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600">منو:</label>
            <select wire:model.live="filterMenuId" class="admin-input">
                <option value="">همه منوها</option>
                @foreach($menus as $menu)
                    <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                @endforeach
            </select>
        </div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا آدرس..." class="admin-input sm:w-80">
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">عنوان</th>
                    <th class="admin-th">منو</th>
                    <th class="admin-th">نوع</th>
                    <th class="admin-th">ترتیب</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $item->id }}</td>
                        <td class="admin-td font-medium">{{ $item->title }}</td>
                        <td class="admin-td text-gray-500">{{ $item->menu?->name ?? '—' }}</td>
                        <td class="admin-td">
                            @if($item->route_key)
                                <span class="admin-badge bg-blue-50 text-blue-600 text-xs">سیستمی</span>
                            @else
                                <span class="admin-badge bg-gray-100 text-gray-600 text-xs" dir="ltr">{{ $item->item_type->value }}</span>
                            @endif
                        </td>
                        <td class="admin-td text-gray-500">{{ $item->sort_order }}</td>
                        <td class="admin-td">
                            <span class="{{ $item->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="admin-td">
                            @if($item->route_key)
                                {{-- System items are managed via the appearance page --}}
                                <a href="{{ route('admin.appearance') }}" class="text-yellow-500 hover:text-yellow-700 text-xs">مدیریت در ظاهر سایت</a>
                            @else
                                <button wire:click="edit({{ $item->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                                <button wire:click="delete({{ $item->id }})" wire:confirm="آیا از حذف این آیتم منو مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="admin-empty">آیتم منویی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $items->links() }}</div>
    </div>
</div>