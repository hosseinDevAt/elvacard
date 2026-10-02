<div>
    <x-admin.page-header title="مدیریت محصولات فروشگاه" subtitle="مدیریت، دسته‌بندی و بررسی وضعیت موجودی محصولات فروشگاه">
        <x-slot:actions>
            <button type="button" wire:click="create" type="button" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-[#ffde5b]/25">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>افزودن محصول جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش محصول' : 'محصول جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                @if(! $customizationWorkflow)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="font-bold text-gray-900 mb-1">نوع قیمت‌گذاری</h4>
                        <p class="text-sm text-gray-500 mb-3">تعیین کنید قیمت این محصول ثابت است یا به‌صورت متغیر و بر اساس رنگ‌های آن تعیین می‌شود.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 rounded-lg border p-4 cursor-pointer transition-colors duration-150 group {{ $pricingType === 'simple'
                                ? 'border-yellow-500 bg-yellow-50 ring-1 ring-yellow-500/60'
                                : 'border-gray-300 hover:border-yellow-400 hover:bg-yellow-50/60' }}">
                                <input type="radio" name="pricingType" wire:model.live="pricingType" value="simple"
                                    class="accent-yellow-500 focus:ring-yellow-500 mt-1" @checked($pricingType === 'simple')>
                                <span class="min-w-0">
                                    <span class="block font-medium text-gray-900 group-hover:text-yellow-900 transition-colors duration-150">محصول عادی</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">یک قیمت پایه ثابت دارد؛ همه به‌یک‌نرخ خرید می‌کنند.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-lg border p-4 cursor-pointer transition-colors duration-150 group {{ $pricingType === 'variable'
                                ? 'border-yellow-500 bg-yellow-50 ring-1 ring-yellow-500/60'
                                : 'border-gray-300 hover:border-yellow-400 hover:bg-yellow-50/60' }}">
                                <input type="radio" name="pricingType" wire:model.live="pricingType" value="variable"
                                    class="accent-yellow-500 focus:ring-yellow-500 mt-1" @checked($pricingType === 'variable')>
                                <span class="min-w-0">
                                    <span class="block font-medium text-gray-900 group-hover:text-yellow-900 transition-colors duration-150">محصول متغیر</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">برای هر رنگ محصول قیمت جداگانه ثبت می‌شود.</span>
                                </span>
                            </label>
                        </div>
                        @error('pricingType') <p class="admin-error mt-2">{{ $message }}</p> @enderror

                        @if($pricingType === 'variable')
                            <div class="mt-3 rounded-lg border border-violet-200 bg-violet-50 p-4">
                                <h5 class="font-bold text-violet-900 text-sm mb-1">قیمت‌گذاری متغیر</h5>
                                <p class="text-sm text-violet-800">قیمت فروش این محصول از روی رنگ‌های فعال آن محاسبه می‌شود و قیمت هر رنگ را جداگانه ثبت می‌کنید.</p>
                                @if($editingId)
                                    <p class="text-sm text-violet-800 mt-1">رنگ‌ها و قیمت‌ها از بخش «متغیرهای محصول» همین صفحه مدیریت می‌شوند.</p>
                                @else
                                    <p class="text-sm text-violet-800 mt-1">برای شروع، محصول را ذخیره کنید؛ بلافاصله وارد بخش تنظیم رنگ‌ها و قیمت‌ها می‌شوید.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">نوع محصول</label>
                        <select wire:model="type" class="admin-select">
                            @foreach($typeOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">فرآیند شخصی‌سازی</label>
                        <select wire:model="customizationWorkflow" class="admin-select">
                            @foreach($workflowOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('customizationWorkflow') <p class="admin-error">{{ $message }}</p> @enderror
                        @if($customizationWorkflow === \App\Enums\CustomizationWorkflowEnum::FUEL_CARD->value)
                            <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-xs text-amber-800 space-y-1">
                                <p class="font-bold text-amber-900">پیش‌نیازهای فعال‌سازی و فروش کارت سوخت:</p>
                                <p class="text-amber-900">این محصول تا تکمیل پیش‌نیازهای زیر قابل فعال‌سازی نیست:</p>
                                <ul class="list-disc ms-4 space-y-0.5">
                                    @foreach($fuelPreparation as $item)
                                        <li>
                                             {{ $item['label'] }}
                                            @if($editingId)
                                                @if($item['ok'])
                                                    <span class="text-emerald-600 font-semibold">✓ تکمیل شده</span>
                                                @else
                                                    <span class="text-rose-600 font-semibold">✗ لازم است</span>
                                                @endif
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if($editingId)
                                    <div class="pt-1">
                                        <a href="{{ route('admin.product-colors', ['product' => $editingId]) }}" class="text-[#010619] hover:underline font-bold font-semibold underline">مدیریت رنگ و قیمت این محصول</a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="admin-label">نام محصول</label>
                        <input type="text" wire:model="name" class="admin-input">
                        @error('name') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label">توضیحات</label>
                    <textarea wire:model="description" rows="3" class="admin-input"></textarea>
                    @error('description') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">تصویر اصلی</label>
                        <input type="text" wire:model="mainImage" placeholder="products/card.jpg" dir="ltr" class="admin-input">
                        <input type="file" wire:model="mainImageUpload" accept="image/*" class="block w-full mt-2 text-xs text-slate-600 file:me-3 file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-slate-700 file:rounded-xl file:font-medium file:cursor-pointer hover:file:bg-slate-200">
                        @error('mainImage') <p class="admin-error">{{ $message }}</p> @enderror
                        @error('mainImageUpload') <p class="admin-error">{{ $message }}</p> @enderror
                        @if($mainImageUpload)
                            <img src="{{ $mainImageUpload->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-xl border border-slate-200" alt="">
                        @elseif($mainImage)
                            <img src="{{ asset('storage/'.$mainImage) }}" class="mt-2 h-24 w-24 object-cover rounded-xl border border-slate-200" alt="">
                        @endif
                    </div>
                    @if($customizationWorkflow)
                        <div>
                            <label class="admin-label">قیمت پایه (تومان)</label>
                            <input type="number" wire:model="basePrice" min="0" placeholder="500000" dir="ltr" class="admin-input">
                            @error('basePrice') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                    @elseif($pricingType === 'simple')
                        <div>
                            <label class="admin-label">قیمت پایه (تومان)</label>
                            <input type="number" wire:model="basePrice" min="0" placeholder="500000" dir="ltr" class="admin-input">
                            @error('basePrice') <p class="admin-error">{{ $message }}</p> @enderror
                            <p class="text-xs text-slate-400 mt-1">قیمت ثابت محصول عادی؛ مشتری این مبلغ را پرداخت می‌کند.</p>
                        </div>
                    @else
                        <div>
                            <label class="admin-label">قیمت متغیر</label>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-xs text-slate-700">
                                @if($editingId)
                                    قیمت هر رنگ از بخش «متغیرهای محصول» همین صفحه مدیریت می‌شود.
                                @else
                                    محصول بدون قیمت پایه ذخیره می‌شود؛ پس از ذخیره قیمت هر رنگ را تعیین می‌کنید.
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                @if(! $customizationWorkflow)
                    <div>
                        <label class="admin-label">دسته‌بندی محصول</label>
                        <select wire:model="productCategoryId" class="admin-select">
                            <option value="">بدون دسته‌بندی</option>
                            @foreach($categoryOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}@if(! $option->is_active) (غیرفعال)@endif</option>
                            @endforeach
                        </select>
                        @error('productCategoryId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="admin-label">پیکربندی طراحی (JSON، اختیاری)</label>
                    <textarea wire:model="designConfig" rows="2" dir="ltr" placeholder='{"note":"رایگان"}' class="admin-input"></textarea>
                    @error('designConfig') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                        <label for="is_active" class="text-xs font-semibold text-slate-700">فعال (نمایش در فروشگاه)</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                        <label for="robots_index" class="text-xs font-semibold text-slate-700">قابل ایندکس در موتورهای جستجو</label>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <h4 class="font-bold text-slate-900 mb-3">سئو</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="admin-label">عنوان سئو</label>
                            <input type="text" wire:model="metaTitle" class="admin-input">
                            @error('metaTitle') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">توضیحات سئو</label>
                            <textarea wire:model="metaDescription" rows="2" class="admin-input"></textarea>
                            @error('metaDescription') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">آدرس Canonical</label>
                            <input type="text" wire:model="canonicalUrl" dir="ltr" class="admin-input">
                            @error('canonicalUrl') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">تصویر Open Graph</label>
                            <input type="text" wire:model="ogImage" dir="ltr" class="admin-input">
                            <input type="file" wire:model="ogImageUpload" accept="image/*" class="block w-full mt-2 text-xs text-slate-600 file:me-3 file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-slate-700 file:rounded-xl file:font-medium file:cursor-pointer hover:file:bg-slate-200">
                            @error('ogImage') <p class="admin-error">{{ $message }}</p> @enderror
                            @error('ogImageUpload') <p class="admin-error">{{ $message }}</p> @enderror
                            @if($ogImageUpload)
                                <img src="{{ $ogImageUpload->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-xl border border-slate-200" alt="">
                            @elseif($ogImage)
                                <img src="{{ asset('storage/'.$ogImage) }}" class="mt-2 h-24 w-24 object-cover rounded-xl border border-slate-200" alt="">
                            @endif
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="admin-label">محتوی سئو</label>
                        <textarea wire:model="seoContent" rows="3" class="admin-input"></textarea>
                        @error('seoContent') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">{{ $editingId ? 'ذخیره تغییرات' : ($pricingType === 'variable' ? 'ایجاد محصول و تنظیم قیمت‌ها' : 'ایجاد محصول') }}</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                </div>
            </form>

            @if($editingId && ! $customizationWorkflow && $pricingType === 'variable')
                <div class="border-t border-slate-100 pt-4 mt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-slate-900">متغیرهای محصول</h4>
                        <button type="button" wire:click="openVariantForm" class="admin-btn admin-btn-primary admin-btn-sm text-xs font-semibold">
                            + افزودن رنگ و قیمت
                        </button>
                    </div>

                    @if($showVariantForm)
                        <div class="bg-slate-50/70 rounded-xl border border-slate-200 p-4 mb-4">
                            <h5 class="font-bold text-slate-800 text-sm mb-3">{{ $editingVariantId ? 'ویرایش رنگ و قیمت' : 'افزودن رنگ و قیمت' }}</h5>
                            <form wire:submit="saveVariant" class="space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="admin-label">رنگ</label>
                                        <select wire:model="variantColorId" class="admin-select">
                                            <option value="">— انتخاب رنگ —</option>
                                            @foreach($storeColorOptions as $option)
                                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('variantColorId') <p class="admin-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="admin-label">قیمت (تومان)</label>
                                        <input type="number" wire:model="variantPrice" min="0" dir="ltr" class="admin-input">
                                        @error('variantPrice') <p class="admin-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex items-end pb-2">
                                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                                            <input type="checkbox" wire:model="variantIsActive" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                                            <span>فعال (قابل فروش)</span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label class="admin-label">تصاویر این رنگ (اختیاری)</label>
                                    <input type="file" wire:model="variantImageUploads" multiple accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml" class="admin-input sm:w-96 text-xs">
                                    @error('variantImageUploads.*') <p class="admin-error">{{ $message }}</p> @enderror
                                    <p class="text-[11px] text-slate-400 mt-1">تصاویر جی‌پی‌جی، پی‌ان‌جی، وب‌پی یا اس‌وی‌جی تا ۲ مگابایت؛ اگر این رنگ تصویر اصلی نداشته باشد، اولین تصویر همان می‌شود.</p>
                                </div>
                                <div class="flex items-center gap-3 pt-2">
                                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">ذخیره</button>
                                    <button type="button" wire:click="$set('showVariantForm', false); $wire.resetVariantForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if($storeVariants->isEmpty())
                        <div class="py-4">
                            <x-admin.empty-state title="رنگی ثبت نشده است" description="با کلیک روی «+ افزودن رنگ و قیمت» اولین متغیر محصول را اضافه کنید." />
                        </div>
                    @else
                        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50/70 border-b border-slate-100">
                                    <tr>
                                        <th class="admin-th">#</th>
                                        <th class="admin-th">رنگ</th>
                                        <th class="admin-th">قیمت</th>
                                        <th class="admin-th">وضعیت</th>
                                        <th class="admin-th">تصاویر</th>
                                        <th class="admin-th text-center">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($storeVariants as $variant)
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="admin-td text-slate-400 font-mono text-xs">{{ $variant->id }}</td>
                                            <td class="admin-td">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-5 h-5 rounded-full border border-white shadow-xs" style="background-color: {{ $variant->color?->code_hex }}"></div>
                                                    <span class="text-xs font-semibold text-slate-700">{{ $variant->color?->name }}</span>
                                                </div>
                                            </td>
                                            <td class="admin-td font-extrabold text-slate-900 text-xs" dir="ltr">{{ number_format((int) $variant->price) }} تومان</td>
                                            <td class="admin-td">
                                                <span class="admin-badge {{ $variant->is_active ? 'admin-badge-success' : 'admin-badge-neutral' }}">{{ $variant->is_active ? 'فعال' : 'غیرفعال' }}</span>
                                            </td>
                                            <td class="admin-td">
                                                @if($variant->images->isEmpty())
                                                    <span class="text-xs text-slate-400">بدون تصویر</span>
                                                @else
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($variant->images as $image)
                                                            <div class="flex flex-col items-center gap-1">
                                                                <div class="relative">
                                                                    <img src="{{ asset('storage/' . $image->image_path) }}" alt="" class="h-10 w-10 rounded-xl border border-slate-200 object-cover shadow-xs">
                                                                    @if($image->is_primary)
                                                                        <span class="absolute -top-1 -start-1 rounded-full bg-emerald-500 px-1 text-[8px] font-medium text-white">اصلی</span>
                                                                    @endif
                                                                </div>
                                                                @if(! $image->is_primary)
                                                                    <button type="button" wire:click="setPrimaryVariantImage({{ $image->id }})" class="text-[10px] text-[#010619] hover:underline font-bold">تعیین اصلی</button>
                                                                @endif
                                                                <button type="button" wire:click="deleteVariantImage({{ $image->id }})" wire:confirm="آیا از حذف این تصویر مطمئن هستید؟" class="text-[10px] text-rose-600 hover:text-rose-700">حذف</button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="admin-td text-center">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <button type="button" wire:click="editVariant({{ $variant->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                                    <button type="button" wire:click="deleteVariant({{ $variant->id }})" wire:confirm="آیا از حذف این قیمت مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs px-2 py-1.5 font-medium transition">حذف</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if(! $storeVariants->contains(fn ($variant) => (bool) $variant->is_active))
                        <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-xs text-amber-800">
                            این محصول هنوز قابل فعال‌سازی نیست؛ حداقل یک رنگ فعال با قیمت معتبر اضافه کنید.
                        </p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <!-- Card Header & Filter Bar -->
        <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-base font-bold text-slate-900">لیست کل محصولات</h3>

            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-semibold text-slate-400">فیلترها:</span>
                <div class="w-full sm:w-56">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام محصول..."
                           class="admin-input py-2 text-xs">
                </div>
                <div class="w-full sm:w-40">
                    <select wire:model.live="typeFilter" class="admin-select py-2 text-xs">
                        @foreach($typeFilterOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <select wire:model.live="workflowFilter" class="admin-select py-2 text-xs">
                        @foreach($workflowFilterOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th w-20">تصویر</th>
                        <th class="admin-th">نام محصول</th>
                        <th class="admin-th">قیمت</th>
                        <th class="admin-th">موجودی انبار</th>
                        <th class="admin-th">نوع محصول</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $product->id }}</td>
                            <td class="admin-td">
                                @if($product->main_image)
                                    <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}"
                                         class="h-12 w-12 rounded-xl object-cover border border-slate-200/80 shadow-xs">
                                @else
                                    <div class="h-12 w-12 rounded-xl bg-slate-100 border border-slate-200/60 flex items-center justify-center text-slate-400">
                                        <x-icons.box class="h-6 w-6 text-slate-400" />
                                    </div>
                                @endif
                            </td>
                            <td class="admin-td">
                                <span class="font-bold text-slate-900 block text-sm">{{ $product->name }}</span>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if($product->category)
                                        <span class="text-xs text-slate-400">{{ $product->category->name }}</span>
                                    @endif
                                    <span class="text-[10px] text-slate-400 font-mono" dir="ltr">{{ $product->slug }}</span>
                                    @if($product->customization_workflow)
                                        <span class="admin-badge bg-[#010619]/10 text-[#010619] border border-[#010619]/20 text-[10px] px-2 py-0.5">
                                            {{ $product->customization_workflow->faLabel() }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="admin-td">
                                @if($product->color_prices_count > 0)
                                    <span class="font-bold text-slate-900 block text-sm">
                                        {{ $product->active_color_prices_min_price !== null ? 'از ' . number_format($product->active_color_prices_min_price) . ' تومان' : 'تغییر قیمت بر اساس رنگ' }}
                                    </span>
                                @elseif($product->base_price !== null)
                                    <span class="font-bold text-slate-900 block text-sm">
                                        {{ number_format($product->base_price) }} تومان
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 block">—</span>
                                @endif

                                @if($product->customization_workflow)
                                    <a href="{{ route('admin.product-colors', ['product' => $product->id]) }}" class="inline-flex items-center gap-1 text-[#010619] hover:underline font-bold text-xs font-semibold mt-1">
                                        قیمت کارت ({{ $product->color_prices_count }})
                                    </a>
                                @else
                                    <span class="text-[11px] text-slate-400">مدیریت از فرم محصول</span>
                                @endif
                            </td>
                            <td class="admin-td">
                                @if(! $product->is_active)
                                    <span class="admin-badge admin-badge-neutral">غیرفعال</span>
                                @elseif(in_array($product->id, $purchasableIds, true))
                                    <span class="admin-badge admin-badge-success">قابل فروش</span>
                                @else
                                    <span class="admin-badge admin-badge-danger">قابل فروش نیست</span>
                                @endif
                            </td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $product->color_prices_count > 0 ? 'admin-badge-info' : 'admin-badge-neutral' }}">
                                    {{ $product->color_prices_count > 0 ? 'متغیر' : 'ساده' }}
                                </span>
                            </td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $product->is_active ? 'admin-badge-success' : 'admin-badge-neutral' }}">
                                    {{ $product->is_active ? 'منتشر شده' : 'پیش‌نویس' }}
                                </span>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" wire:click="edit({{ $product->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">
                                        ویرایش
                                    </button>
                                    <button type="button" wire:click="delete({{ $product->id }})" wire:confirm="آیا از حذف این محصول مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">
                                        حذف
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8">
                                <x-admin.empty-state title="محصولی یافت نشد" description="هیچ محصولی مطابق با فیلترهای انتخابی پیدا نشد." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $products->links() }}</div>
    </div>
</div>