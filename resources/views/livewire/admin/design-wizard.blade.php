<div>
    @php
        $stepLabels = [1 => 'اطلاعات', 2 => 'تصاویر', 3 => 'رنگ‌ها', 4 => 'سازگاری', 5 => 'بررسی'];
    @endphp

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $design ? 'ویرایش طرح' : 'طرح جدید' }}</h1>
        <a href="{{ route('admin.designs') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition">
            بازگشت به فهرست طرح‌ها
        </a>
    </div>

    {{-- Step indicator --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 overflow-x-auto">
        <ol class="flex items-center gap-2 min-w-[560px]">
            @foreach($stepLabels as $number => $label)
                @php
                    $isCurrent = $step === $number;
                    $isDone = $step > $number;
                @endphp
                <li class="flex items-center gap-2">
                    <div class="flex items-center gap-2">
                        <span aria-hidden="true"
                              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold transition {{ $isCurrent ? 'bg-yellow-500 text-white' : ($isDone ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                            {{ $isDone ? '✓' : $number }}
                        </span>
                        <span class="text-sm {{ $isCurrent ? 'font-bold text-gray-900' : 'text-gray-500' }}">{{ $label }}</span>
                        @if($step === $number)
                            <span class="sr-only">مرحله فعلی</span>
                        @endif
                    </div>
                    @if($number < 5)
                        <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    @if(session()->has('success'))
        <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 text-sm">{{ session('success') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Step ① اطلاعات --}}
    @if($step === 1)
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">دسته‌بندی طرح</label>
                    <select wire:model="cateDesignId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        <option value="">— انتخاب دسته‌بندی —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (غیرفعال)' }}</option>
                        @endforeach
                    </select>
                    @error('cateDesignId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                    <button type="button" wire:click="$toggle('showCategoryForm')" class="mt-2 text-xs text-yellow-600 hover:text-yellow-700 hover:underline">
                        {{ $showCategoryForm ? 'بستن فرم دسته‌بندی جدید' : '+ دسته‌بندی جدید' }}
                    </button>

                    @if($showCategoryForm)
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <input type="text" wire:model="newCategoryName" placeholder="نام دسته‌بندی جدید"
                                   class="w-full sm:w-56 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
                            <button type="button" wire:click="addCategory" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition">
                                افزودن
                            </button>
                        </div>
                        @error('newCategoryName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نام طرح</label>
                    <input type="text" wire:model="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                <textarea wire:model="description" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ترتیب نمایش</label>
                    <input type="number" wire:model="sortOrder" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('sortOrder') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                    <label for="is_active" class="text-sm text-gray-700">فعال (نمایش در سفارشی‌ساز)</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-gray-300 text-yellow-500">
                    <label for="robots_index" class="text-sm text-gray-700">قابل ایندکس در موتورهای جستجو</label>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <h4 class="font-bold text-gray-900 mb-3">سئو</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">عنوان سئو</label>
                        <input type="text" wire:model="metaTitle" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('metaTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات سئو</label>
                        <textarea wire:model="metaDescription" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                        @error('metaDescription') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">آدرس Canonical</label>
                        <input type="text" wire:model="canonicalUrl" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('canonicalUrl') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">محتوی سئو</label>
                    <textarea wire:model="seoContent" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                    @error('seoContent') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    @endif

    {{-- Step ② تصاویر --}}
    @if($step === 2)
        @if(! $designId)
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 mb-6">
                ابتدا در مرحله «اطلاعات» نام و دسته‌بندی طرح را ثبت کنید.
            </div>
        @else
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 mb-4">{{ $editingImageId ? 'ویرایش تصویر' : 'افزودن تصویر' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">رنگ تصویر</label>
                            <select wire:model="colorId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                                <option value="">— انتخاب رنگ —</option>
                                @foreach($colors as $color)
                                    <option value="{{ $color->id }}">{{ $color->name }}</option>
                                @endforeach
                            </select>
                            @error('colorId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">مسیر تصویر</label>
                            <input type="text" wire:model="imagePath" placeholder="designs/eagle-black.png" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('imagePath') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            <input type="file" wire:model="imageUpload" accept="image/*" class="block w-full mt-2 text-sm text-gray-600 file:me-3 file:border-0 file:bg-yellow-50 file:px-4 file:py-2 file:text-yellow-700 file:cursor-pointer">
                            @error('imageUpload') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            @if($imageUpload)
                                <img src="{{ $imageUpload->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-lg border border-gray-200" alt="">
                            @elseif($editingImageId && $imagePath)
                                <img src="{{ asset('storage/'.$imagePath) }}" class="mt-2 h-24 w-24 object-cover rounded-lg border border-gray-200" alt="">
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">متن جایگزین (Alt)</label>
                            <input type="text" wire:model="altText" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('altText') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">عنوان تصویر</label>
                            <input type="text" wire:model="imageTitle" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('imageTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">فایل بهینه‌شده</label>
                            <input type="text" wire:model="optimizedFilename" placeholder="eagle-black-optimized.webp" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('optimizedFilename') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ترتیب نمایش</label>
                            <input type="number" wire:model="imageSortOrder" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('imageSortOrder') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">کپشن سئو</label>
                        <textarea wire:model="seoCaption" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                        @error('seoCaption') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <input type="checkbox" wire:model="imageIsActive" id="image_is_active" class="rounded border-gray-300 text-yellow-500">
                        <label for="image_is_active" class="text-sm text-gray-700">فعال (نمایش در سفارشی‌ساز)</label>
                    </div>
                    <div class="mt-4 flex items-center gap-4">
                        <button type="button" wire:click="saveImage" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره تصویر</button>
                        @if($editingImageId)
                            <button type="button" wire:click="resetImageForm" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">انصراف از ویرایش</button>
                        @endif
                    </div>
                </div>

                @if($images->isEmpty())
                    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                        هنوز تصویری برای این طرح ثبت نشده است.
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500">رنگ</th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500">مسیر</th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500">ترتیب</th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($images as $image)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-500">{{ $image->id }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-5 h-5 rounded border" style="background-color: {{ $image->color?->code_hex }}"></div>
                                                <span>{{ $image->color?->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 font-mono text-xs break-all" dir="ltr">{{ $image->image_path }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $image->sort_order }}</td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $image->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $image->is_active ? 'فعال' : 'غیرفعال' }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <button type="button" wire:click="editImage({{ $image->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                                            <button type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="آیا از حذف این تصویر (به همراه سازگاری‌هایش) مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Step ③ رنگ‌ها --}}
    @if($step === 3)
        @if($images->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 mb-6">
                این طرح هنوز تصویری ندارد؛ ابتدا در مرحله «تصاویر» تصویر اضافه کنید.
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-2">رنگ‌های مرتبط با طرح</h3>
                <p class="text-sm text-gray-500 mb-6">رنگ‌هایی که برای این طرح تصویر ثبت کرده‌اید. در مرحله بعد، سازگاری هر تصویر با رنگ‌های کارت را تعیین کنید.</p>

                @if(empty($colorOverview))
                    <p class="text-gray-400 text-center py-6">رنگی یافت نشد.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($colorOverview as $color)
                            <div class="flex items-center gap-3 rounded-xl border border-gray-200 p-4">
                                <div class="w-8 h-8 rounded-full border shadow-inner" style="background-color: {{ $color['hex'] }}"></div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $color['name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $color['count'] }} تصویر</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Step ④ سازگاری --}}
    @if($step === 4)
        @if($images->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 mb-6">
                این طرح تصویری ندارد؛ ابتدا در مرحله «تصاویر» تصویر اضافه کنید.
            </div>
        @else
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-sm text-gray-500 mb-4">برای هر تصویر طرح، سازگاری با رنگ‌های کارت را با تیک مشخص کنید. فقط ترکیب‌های دارای تیک در سفارشی‌ساز نمایش داده می‌شوند.</p>
                    @foreach($compatibilityRows as $row)
                        <div class="rounded-xl border border-gray-200 p-5 mb-4">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm">{{ $row['image']->image_path }}</h4>
                                    <p class="text-sm text-gray-500 mt-1">
                                        رنگ تصویر:
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-3 h-3 rounded-full inline-block" style="background-color: {{ $row['image']->color?->code_hex }}"></span>
                                            {{ $row['image']->color?->name }}
                                        </span>
                                        | {{ $row['allowedCount'] }} از {{ count($colors) }} رنگ مجاز
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                @foreach($colors as $color)
                                    @php $allowed = $row['map'][$color->id] ?? false; @endphp
                                    <button
                                        type="button"
                                        wire:click="toggleCompatibility({{ $row['image']->id }}, {{ $color->id }})"
                                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition {{ $allowed ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-white' }}"
                                    >
                                        <input type="checkbox" {{ $allowed ? 'checked' : '' }} class="rounded border-gray-300 pointer-events-none">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-3 h-3 rounded-full inline-block" style="background-color: {{ $color->code_hex }}"></span>
                                            {{ $color->name }}
                                        </span>
                                        <span class="text-xs {{ $allowed ? 'text-green-600' : 'text-gray-400' }}">{{ $allowed ? 'مجاز' : 'نامجاز' }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- Step ⑤ بررسی --}}
    @if($step === 5)
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-bold text-gray-900 mb-6">بررسی نهایی طرح</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">نام طرح</dt>
                    <dd class="font-medium text-gray-900 text-end">{{ $summary['name'] ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">دسته‌بندی</dt>
                    <dd class="font-medium text-gray-900 text-end">{{ $summary['category'] ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">اسلاگ</dt>
                    <dd class="font-medium text-gray-900 font-mono text-xs text-end" dir="ltr">{{ $summary['slug'] ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">وضعیت</dt>
                    <dd class="font-medium text-end {{ $summary['isActive'] ? 'text-green-600' : 'text-red-500' }}">{{ $summary['isActive'] ? 'فعال' : 'غیرفعال' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">ترتیب نمایش</dt>
                    <dd class="font-medium text-gray-900 text-end">{{ $summary['sortOrder'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">تصاویر ثبت‌شده</dt>
                    <dd class="font-medium text-gray-900 text-end">{{ $summary['imagesCount'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">رنگ‌های مرتبط</dt>
                    <dd class="font-medium text-gray-900 text-end">{{ $summary['colorsCount'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 pb-3">
                    <dt class="text-gray-500">ترکیبات مجاز (تصویر × رنگ)</dt>
                    <dd class="font-medium text-gray-900 text-end">{{ $summary['allowedCombos'] }}</dd>
                </div>
            </dl>
            @if($summary['description'])
                <div class="mt-6 border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-900 text-sm mb-2">توضیحات</h4>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $summary['description'] }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Wizard footer navigation --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
        <button type="button"
                wire:click="back"
                @if($step === 1) disabled @endif
                class="inline-flex items-center gap-1 bg-gray-200 text-gray-700 px-6 py-2 rounded-lg text-sm hover:bg-gray-300 transition disabled:opacity-40 disabled:cursor-not-allowed">
            مرحله قبل
        </button>

        @if($step < 5)
            <button type="button"
                    wire:click="next"
                    class="inline-flex items-center gap-1 bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
                مرحله بعد
            </button>
        @else
            <button type="button"
                    wire:click="save"
                    class="inline-flex items-center gap-1 bg-green-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-green-700 transition">
                ذخیره طرح
            </button>
        @endif
    </div>
</div>