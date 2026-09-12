<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">آیتم‌های منو</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + آیتم منو جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش آیتم منو' : 'آیتم منو جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">منو</label>
                        <select wire:model="menuId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">انتخاب منو...</option>
                            @foreach($menus as $menu)
                                <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                            @endforeach
                        </select>
                        @error('menuId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نوع آیتم</label>
                        <select wire:model="itemType" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="url">لینک خارجی</option>
                            <option value="page">صفحه</option>
                            <option value="product">محصول</option>
                            <option value="design">طرح</option>
                            <option value="article">مقاله</option>
                        </select>
                        @error('itemType') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عنوان</label>
                    <input type="text" wire:model="title" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if($itemType === 'url')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">آدرس</label>
                        <input type="text" wire:model="customUrl" placeholder="https://example.com یا /pages/..." dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('customUrl') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'page')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">صفحه</label>
                        <select wire:model="targetId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">انتخاب صفحه...</option>
                            @foreach($pages as $page)
                                <option value="{{ $page->id }}">{{ $page->title }} ({{ $page->slug }})</option>
                            @endforeach
                        </select>
                        @error('targetId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'product')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">محصول</label>
                        <select wire:model="targetId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">انتخاب محصول...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('targetId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'design')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">طرح</label>
                        <select wire:model="targetId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">انتخاب طرح...</option>
                            @foreach($designs as $design)
                                <option value="{{ $design->id }}">{{ $design->name }}</option>
                            @endforeach
                        </select>
                        @error('targetId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'article')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">مقاله</label>
                        <select wire:model="targetId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">انتخاب مقاله...</option>
                            @foreach($articles as $article)
                                <option value="{{ $article->id }}">{{ $article->title }}</option>
                            @endforeach
                        </select>
                        @error('targetId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ترتیب</label>
                        <input type="number" wire:model="sortOrder" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('sortOrder') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">باز شدن در</label>
                        <select wire:model="target" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="_self">همان برگه</option>
                            <option value="_blank">برگه جدید</option>
                        </select>
                        @error('target') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
            <select wire:model.live="filterMenuId" class="px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                <option value="">همه منوها</option>
                @foreach($menus as $menu)
                    <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                @endforeach
            </select>
        </div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا آدرس..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عنوان</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">منو</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">نوع</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">ترتیب</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $item->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->title }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->menu?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($item->route_key)
                                <span class="inline-block px-2 py-1 rounded-full bg-blue-50 text-blue-600 text-xs">سیستمی</span>
                            @else
                                <span class="inline-block px-2 py-1 rounded-full bg-gray-100 text-gray-600 text-xs" dir="ltr">{{ $item->item_type->value }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->sort_order }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $item->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($item->route_key)
                                {{-- System items are managed via the appearance page --}}
                                <a href="{{ route('admin.appearance') }}" class="text-yellow-500 hover:text-yellow-700 text-xs">مدیریت در ظاهر سایت</a>
                            @else
                                <button wire:click="edit({{ $item->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                                <button wire:click="delete({{ $item->id }})" wire:confirm="آیا از حذف این آیتم منو مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">آیتم منویی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $items->links() }}</div>
    </div>
</div>