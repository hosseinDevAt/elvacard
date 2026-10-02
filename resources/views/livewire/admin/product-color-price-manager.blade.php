<div>
    <x-admin.page-header title="طرح‌های کار و پایه قیمت‌ها" subtitle="تعیین قیمت پایه بر اساس رنگ کارت‌های بانکی و سوخت">
        <x-slot:actions>
            <button type="button" wire:click="create" type="button" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-[#ffde5b]/25 shrink-0">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>افزودن طرح / قیمت جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($selectedProduct)
        <div class="mb-4 -mt-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-[#ffde5b]/20 text-[#010619] border border-[#ffde5b]/60">
                <span>در حال مدیریت رنگ و قیمت:</span>
                <span class="font-bold">{{ $selectedProduct->name }}</span>
            </span>
        </div>
    @endif

    @if($selectedProductIsFuel)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-xs text-amber-800">
            کارت سوخت فقط یک رنگ و قیمت فعال می‌پذیرد؛ ردیف‌های غیرفعال اضافی مجاز هستند.
        </div>
    @endif

    <!-- Base Price Cards by Color (Matching Figma Reference 2) -->
    <div class="admin-card p-6 mb-6">
        <h3 class="text-sm font-bold text-slate-700 mb-5">لیست قیمت پایه بر اساس رنگ کارت</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
            @forelse($prices->take(5) as $cardPrice)
                <div class="flex flex-col items-center justify-center p-5 rounded-2xl border border-slate-100 bg-slate-50/60 hover:bg-white hover:shadow-sm hover:border-slate-300 transition group text-center">
                    <div class="w-12 h-12 rounded-full border-2 border-white shadow-md mb-3 transition-transform group-hover:scale-105"
                         style="background-color: {{ $cardPrice->color?->code_hex ?? '#ccc' }};"></div>
                    <span class="text-xs font-bold text-slate-800 block mb-1">{{ $cardPrice->color?->name ?? 'رنگ' }}</span>
                    <span class="text-xs font-extrabold text-slate-900 block">{{ number_format((int)$cardPrice->price) }} تومان</span>
                </div>
            @empty
                <div class="col-span-full py-6 text-center text-xs text-slate-400">
                    قیمتی برای رنگ‌های پایه ثبت نشده است.
                </div>
            @endforelse
        </div>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش قیمت' : 'قیمت جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">کارت بانکی / سوخت</label>
                        <select wire:model="productId" class="admin-select">
                            <option value="">— انتخاب محصول —</option>
                            @foreach($productOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('productId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">رنگ</label>
                        <select wire:model="colorId" class="admin-select">
                            <option value="">— انتخاب رنگ —</option>
                            @foreach($colorOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('colorId') <p class="admin-error">{{ $message }}</p> @enderror
                        @if($selectedProductIsFuel)
                            <p class="text-xs text-amber-600 mt-1">کارت سوخت فقط یک رنگ فعال می‌پذیرد؛ برای تعویض رنگ فعال، ابتدا رنگ فعال فعلی را غیرفعال کنید.</p>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="admin-label">قیمت (تومان)</label>
                    <input type="number" wire:model="price" min="0" dir="ltr" class="admin-input sm:w-72">
                    @error('price') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                    <label for="is_active" class="text-xs font-semibold text-slate-700">فعال (قابل فروش)</label>
                </div>
                <div>
                    <label for="image_uploads" class="admin-label">تصاویر این رنگ (اختیاری)</label>
                    <input type="file" id="image_uploads" wire:model="imageUploads" multiple accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml" class="admin-input sm:w-96 text-xs">
                    @error('imageUploads.*') <p class="admin-error">{{ $message }}</p> @enderror
                    <p class="text-[11px] text-slate-400 mt-1">تصاویر جی‌پی‌جی، پی‌ان‌جی، وب‌پی یا اس‌وی‌جی تا ۲ مگابایت؛ اگر این رنگ تصویر اصلی نداشته باشد، اولین تصویر همان می‌شود.</p>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">ذخیره قیمت</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">جدول کلی قیمت‌ها و موجودی رنگ</h3>
            <div class="w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام محصول..." class="admin-input py-2 text-xs">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">محصول</th>
                        <th class="admin-th">رنگ</th>
                        <th class="admin-th">قیمت</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th">تصاویر</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($prices as $priceItem)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $priceItem->id }}</td>
                            <td class="admin-td font-bold text-slate-900">{{ $priceItem->product?->name }}</td>
                            <td class="admin-td">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-full border border-white shadow-xs" style="background-color: {{ $priceItem->color?->code_hex }}"></div>
                                    <span class="text-xs font-semibold text-slate-700">{{ $priceItem->color?->name }}</span>
                                </div>
                            </td>
                            <td class="admin-td font-extrabold text-slate-900" dir="ltr">{{ number_format((int) $priceItem->price) }} تومان</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $priceItem->is_active ? 'admin-badge-success' : 'admin-badge-neutral' }}">
                                    {{ $priceItem->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td">
                                @if($priceItem->images->isEmpty())
                                    <span class="text-xs text-slate-400">بدون تصویر</span>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($priceItem->images as $image)
                                            <div class="flex flex-col items-center gap-1">
                                                <div class="relative">
                                                    <img src="{{ asset('storage/' . $image->image_path) }}" alt="" class="h-10 w-10 rounded-xl border border-slate-200 object-cover shadow-xs">
                                                    @if($image->is_primary)
                                                        <span class="absolute -top-1 -start-1 rounded-full bg-emerald-500 px-1 text-[8px] font-medium text-white">اصلی</span>
                                                    @endif
                                                </div>
                                                @if($productId)
                                                    @if(! $image->is_primary)
                                                        <button type="button" wire:click="setPrimaryImage({{ $image->id }})" class="text-[10px] text-[#010619] hover:underline font-bold">تعیین اصلی</button>
                                                    @endif
                                                    <button type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="آیا از حذف این تصویر مطمئن هستید؟" class="text-[10px] text-rose-600 hover:text-rose-700">حذف</button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($productId)
                                        <button type="button" wire:click="edit({{ $priceItem->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                        <button type="button" wire:click="delete({{ $priceItem->id }})" wire:confirm="آیا از حذف این قیمت مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                    @else
                                        <button type="button" wire:click="selectProduct({{ $priceItem->product_id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">انتخاب کارت</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8">
                                <x-admin.empty-state title="قیمتی یافت نشد" description="هیچ قیمتی برای رنگ‌ها با فیلتر جستجوی فعلی پیدا نشد." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $prices->links() }}</div>
    </div>
</div>