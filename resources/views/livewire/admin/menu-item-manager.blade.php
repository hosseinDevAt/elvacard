<div>
    <x-admin.page-header
        title="آیتم‌های منو"
        description="مدیریت پیوندها، صفحات، محصولات و مقاصد لینک‌های منوهای سامانه"
    >
        <x-slot:actions>
            <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>آیتم جدید منو</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش آیتم منو' : 'آیتم منو جدید' }}</h3>
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
                            <option value="url">لینک خارجی / دلخواه</option>
                            <option value="page">صفحه متنی</option>
                            <option value="product">محصول</option>
                            <option value="design">طرح</option>
                            <option value="article">مقاله وبلاگ</option>
                        </select>
                        @error('itemType') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="admin-label">عنوان آیتم</label>
                    <input type="text" wire:model="title" class="admin-input" placeholder="متن نمایشی لینک">
                    @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                @if($itemType === 'url')
                    <div>
                        <label class="admin-label">آدرس اینترنتی (URL)</label>
                        <input type="text" wire:model="customUrl" placeholder="https://example.com یا /pages/..." dir="ltr" class="admin-input font-mono text-xs">
                        @error('customUrl') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @elseif($itemType === 'page')
                    <div>
                        <label class="admin-label">انتخاب صفحه</label>
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
                        <label class="admin-label">انتخاب محصول</label>
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
                        <label class="admin-label">انتخاب طرح</label>
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
                        <label class="admin-label">انتخاب مقاله</label>
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
                        <label class="admin-label">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" class="admin-input">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">نحوه باز شدن پیوند</label>
                        <select wire:model="target" class="admin-input">
                            <option value="_self">همان برگه (_self)</option>
                            <option value="_blank">برگه جدید (_blank)</option>
                        </select>
                        @error('target') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end pb-2">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                            <label for="is_active" class="text-sm font-medium text-slate-700">فعال</label>
                        </div>
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

    <div class="flex flex-wrap items-center gap-4 mb-4">
        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-500">فیلتر منو:</label>
            <select wire:model.live="filterMenuId" class="admin-input sm:w-56 text-xs">
                <option value="">همه منوها</option>
                @foreach($menus as $menu)
                    <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative sm:w-80">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا آدرس..." class="admin-input ps-9">
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
                        <th class="admin-th">منو</th>
                        <th class="admin-th">نوع</th>
                        <th class="admin-th">ترتیب</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-slate-400 font-mono text-xs">{{ $item->id }}</td>
                            <td class="admin-td font-medium text-slate-900">{{ $item->title }}</td>
                            <td class="admin-td text-slate-600 text-xs">{{ $item->menu?->name ?? '—' }}</td>
                            <td class="admin-td">
                                @if($item->route_key)
                                    <span class="admin-badge admin-badge-navy text-xs">سیستمی</span>
                                @else
                                    <span class="admin-badge admin-badge-neutral text-xs" dir="ltr">{{ $item->item_type->value }}</span>
                                @endif
                            </td>
                            <td class="admin-td text-slate-500 font-mono text-xs">{{ $item->sort_order }}</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $item->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                                    {{ $item->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td text-center">
                                @if($item->route_key)
                                    {{-- System items are managed via the appearance page --}}
                                    <a href="{{ route('admin.appearance') }}" class="text-[#010619] hover:underline font-bold text-xs font-semibold">مدیریت در ظاهر سایت</a>
                                @else
                                    <div class="inline-flex items-center gap-1.5">
                                        <button wire:click="edit({{ $item->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                        <button wire:click="delete({{ $item->id }})" wire:confirm="آیا از حذف این آیتم منو مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8">
                                <x-admin.empty-state
                                    title="آیتم منویی یافت نشد"
                                    description="هنوز هیچ آیتم منویی برای نمایش وجود ندارد."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $items->links() }}</div>
        @endif
    </div>
</div>