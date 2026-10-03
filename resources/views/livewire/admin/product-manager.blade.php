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
                @if($customizationWorkflow === \App\Enums\CustomizationWorkflowEnum::FUEL_CARD->value)
                    <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-xs text-amber-800 space-y-1">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">نام محصول</label>
                        <input type="text" wire:model="name" class="admin-input">
                        @error('name') <p class="admin-error">{{ $message }}</p> @enderror
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
                </div>

                <div>
                    <label class="admin-label">توضیحات</label>
                    <textarea wire:model="description" rows="3" class="admin-input"></textarea>
                    @error('description') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                {{-- Product Price Section --}}
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
                        <div class="rounded-xl border border-slate-300 bg-slate-50/80 px-4 py-3 text-xs text-slate-700">
                            @if($editingId)
                                قیمت هر رنگ از بخش «متغیرهای محصول» همین صفحه مدیریت می‌شود.
                            @else
                                محصول بدون قیمت پایه ذخیره می‌شود؛ پس از ذخیره قیمت هر رنگ را تعیین می‌کنید.
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Product Image Gallery Section --}}
                <div class="border-t border-slate-200 pt-5 mt-2 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                                <span>تصاویر و گالری محصول</span>
                            </h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                می‌توانید چندین تصویر برای محصول بارگذاری کنید. تصویر با نشان «اصلی» در کاتالوگ فروشگاه و سبد خرید نمایش داده شده و همه تصاویر در گالری صفحه محصول ارائه می‌شوند.
                            </p>
                        </div>
                    </div>

                    {{-- Existing Images Grid (When Editing) --}}
                    @if(! empty($existingImages))
                        <div>
                            <span class="block text-xs font-semibold text-slate-700 mb-2">تصاویر ثبت‌شده در گالری ({{ count($existingImages) }} تصویر)</span>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3.5">
                                @foreach($existingImages as $imgIndex => $img)
                                    <div class="relative group bg-white rounded-2xl border-2 {{ $img['is_primary'] ? 'border-[#010619] shadow-sm' : 'border-slate-200' }} p-2 flex flex-col items-center justify-between transition-all" wire:key="gallery-img-{{ $img['id'] }}">
                                        {{-- Image Thumbnail --}}
                                        <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-slate-100 flex items-center justify-center">
                                            <img src="{{ asset('storage/' . $img['image_path']) }}" alt="تصویر گالری" class="w-full h-full object-cover">
                                            @if($img['is_primary'])
                                                <div class="absolute top-1.5 start-1.5">
                                                    <span class="inline-flex items-center gap-1 bg-[#010619] text-[#ffde5b] text-[10px] font-black px-2 py-0.5 rounded-full shadow-sm">
                                                        <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                        <span>تصویر اصلی</span>
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Image Controls --}}
                                        <div class="w-full mt-2 space-y-1.5">
                                            @if(! $img['is_primary'])
                                                <button type="button"
                                                        wire:click="setPrimaryProductImage({{ $img['id'] }})"
                                                        class="w-full py-1 px-1.5 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-[#ffde5b] hover:text-[#010619] rounded-lg transition-colors cursor-pointer text-center">
                                                    انتخاب به عنوان اصلی
                                                </button>
                                            @endif

                                            <div class="flex items-center justify-between gap-1 pt-1 border-t border-slate-100">
                                                {{-- Reorder Controls --}}
                                                <div class="flex items-center gap-0.5">
                                                    <button type="button"
                                                            wire:click="moveProductImageUp({{ $imgIndex }})"
                                                            @if($imgIndex === 0) disabled @endif
                                                            class="p-1 rounded text-slate-400 hover:text-slate-800 hover:bg-slate-100 disabled:opacity-20 cursor-pointer"
                                                            title="انتقال به قبل">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                                    </button>
                                                    <button type="button"
                                                            wire:click="moveProductImageDown({{ $imgIndex }})"
                                                            @if($imgIndex === count($existingImages) - 1) disabled @endif
                                                            class="p-1 rounded text-slate-400 hover:text-slate-800 hover:bg-slate-100 disabled:opacity-20 cursor-pointer"
                                                            title="انتقال به بعد">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    </button>
                                                </div>

                                                {{-- Delete Control --}}
                                                <button type="button"
                                                        wire:click="deleteProductImage({{ $img['id'] }})"
                                                        wire:confirm="آیا از حذف این تصویر از گالری محصول مطمئن هستید؟"
                                                        class="p-1 rounded text-rose-500 hover:text-rose-700 hover:bg-rose-50 cursor-pointer"
                                                        title="حذف تصویر">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Upload Multiple New Images Area --}}
                    <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/60 p-4 transition-colors hover:border-[#010619]/40">
                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-800 mb-1">بارگذاری تصاویر جدید برای گالری</label>
                                <p class="text-[11px] text-slate-500 mb-2">می‌توانید چندین تصویر را همزمان انتخاب کنید (فرمت‌های JPG, PNG, WEBP تا سقف ۲ مگابایت).</p>
                                <input type="file" wire:model="galleryUploads" multiple accept="image/*" class="block w-full text-xs text-slate-600 file:me-3 file:border-0 file:bg-[#010619] file:text-[#ffde5b] file:px-4 file:py-2 file:text-xs file:rounded-xl file:font-bold file:cursor-pointer hover:file:bg-black cursor-pointer">
                            </div>
                            <div wire:loading wire:target="galleryUploads" class="shrink-0 text-xs font-semibold text-amber-700 bg-amber-50 px-3 py-2 rounded-xl border border-amber-200 flex items-center gap-2">
                                <svg class="animate-spin h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>در حال ارسال تصاویر به سرور...</span>
                            </div>
                        </div>

                        @error('galleryUploads') <p class="admin-error mt-2">{{ $message }}</p> @enderror
                        @error('galleryUploads.*') <p class="admin-error mt-2">{{ $message }}</p> @enderror

                        {{-- Previews of Staged Uploads --}}
                        @if(! empty($galleryUploads))
                            <div class="mt-4 pt-3 border-t border-slate-200">
                                <span class="block text-xs font-bold text-slate-700 mb-2">تصاویر جدید آماده ذخیره ({{ count($galleryUploads) }} مورد):</span>
                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                                    @foreach($galleryUploads as $uploadIdx => $uploadFile)
                                        <div class="relative bg-white rounded-xl border {{ $editingId === null && $primaryUploadIndex === $uploadIdx ? 'border-[#010619] ring-2 ring-[#ffde5b]' : 'border-slate-200' }} p-1.5 flex flex-col items-center gap-1.5 shadow-2xs" wire:key="staged-upload-{{ $uploadIdx }}">
                                            @if($uploadFile)
                                                <div class="relative w-full aspect-square rounded-lg overflow-hidden bg-slate-100">
                                                    <img src="{{ $uploadFile->temporaryUrl() }}" class="w-full h-full object-cover" alt="پیش‌نمایش تصویر">
                                                    @if($editingId === null && $primaryUploadIndex === $uploadIdx)
                                                        <span class="absolute top-1 start-1 bg-[#010619] text-[#ffde5b] text-[9px] font-black px-1.5 py-0.5 rounded-full">
                                                            اصلی
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="w-full flex items-center justify-between gap-1 pt-1">
                                                @if($editingId === null)
                                                    <button type="button"
                                                            wire:click="setPrimaryUpload({{ $uploadIdx }})"
                                                            class="text-[10px] font-bold {{ $primaryUploadIndex === $uploadIdx ? 'text-amber-700' : 'text-slate-500 hover:text-slate-900' }} cursor-pointer">
                                                        {{ $primaryUploadIndex === $uploadIdx ? '✓ اصلی' : 'انتخاب اصلی' }}
                                                    </button>
                                                @endif
                                                <button type="button"
                                                        wire:click="removeGalleryUpload({{ $uploadIdx }})"
                                                        class="text-[10px] font-bold text-rose-500 hover:text-rose-700 cursor-pointer p-0.5"
                                                        title="حذف پیش‌نمایش">
                                                    ✕ حذف
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Direct image path / legacy single upload (preserved for backward compatibility) --}}
                    <details class="text-xs text-slate-500 group">
                        <summary class="cursor-pointer font-medium hover:text-slate-700 flex items-center gap-1 select-none">
                            <span>تنظیمات پیشرفته مسیر فایل تصویر شاخص</span>
                            <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mt-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                            <label class="block text-[11px] font-medium text-slate-600">مسیر مستقیم تصویر شاخص (اختیاری یا ذخیره‌شده):</label>
                            <input type="text" wire:model="mainImage" placeholder="products/card.jpg" dir="ltr" class="admin-input text-xs">
                            <input type="file" wire:model="mainImageUpload" accept="image/*" class="block w-full text-xs text-slate-600 file:me-3 file:border-0 file:bg-slate-200 file:px-3 file:py-1 file:text-slate-700 file:rounded-lg file:cursor-pointer">
                            @error('mainImage') <p class="admin-error">{{ $message }}</p> @enderror
                            @error('mainImageUpload') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                    </details>
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

                {{-- Dynamic Technical Specifications Editor --}}
                <div class="border-t border-slate-100 pt-5 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                                <span>مشخصات فنی و ویژگی‌های محصول</span>
                            </h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                ویژگی‌های اختصاصی این محصول (مانند: جنس بدنه، ابعاد، ضخامت، وزن، سازگاری و...) را تعریف کنید.
                            </p>
                        </div>
                        <button type="button" wire:click="addSpecificationRow" class="admin-btn admin-btn-secondary admin-btn-sm text-xs font-semibold flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>افزودن ردیف مشخصات</span>
                        </button>
                    </div>

                    @if(! empty($specifications))
                        <div class="space-y-3 bg-slate-50/70 p-4 rounded-2xl border border-slate-200">
                            @foreach($specifications as $index => $specRow)
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 p-3 rounded-xl bg-white border border-slate-200/90 shadow-2xs" wire:key="spec-row-{{ $index }}">
                                    {{-- Reorder buttons --}}
                                    <div class="flex items-center gap-1 shrink-0 self-center sm:self-auto">
                                        <button type="button"
                                                wire:click="moveSpecificationUp({{ $index }})"
                                                @if($index === 0) disabled @endif
                                                class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:hover:bg-transparent cursor-pointer"
                                                title="انتقال به بالا">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button type="button"
                                                wire:click="moveSpecificationDown({{ $index }})"
                                                @if($index === count($specifications) - 1) disabled @endif
                                                class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:hover:bg-transparent cursor-pointer"
                                                title="انتقال به پایین">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>

                                    {{-- Specification Label input --}}
                                    <div class="flex-1 min-w-0">
                                        <input type="text"
                                               wire:model="specifications.{{ $index }}.label"
                                               placeholder="عنوان مشخصه (مثال: جنس بدنه، ابعاد، وزن)"
                                               class="admin-input text-xs">
                                        @error('specifications.' . $index . '.label') <p class="admin-error">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- Specification Value input --}}
                                    <div class="flex-1 min-w-0">
                                        <input type="text"
                                               wire:model="specifications.{{ $index }}.value"
                                               placeholder="مقدار مشخصه (مثال: استیل ضدزنگ ۳۱۶L)"
                                               class="admin-input text-xs">
                                        @error('specifications.' . $index . '.value') <p class="admin-error">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- Delete button --}}
                                    <button type="button"
                                            wire:click="removeSpecificationRow({{ $index }})"
                                            class="p-2 rounded-xl text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition shrink-0 cursor-pointer"
                                            title="حذف ردیف">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-xs text-slate-500">
                            <span>هنوز مشخصات فنی برای این محصول تعریف نشده است. با کلیک بر روی «افزودن ردیف مشخصات» می‌توانید مشخصات فنی اختصاصی اضافه کنید.</span>
                        </div>
                    @endif
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