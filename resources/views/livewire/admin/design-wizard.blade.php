<div>
    @php
        $stepLabels = [1 => 'اطلاعات طرح', 2 => 'تصاویر طرح', 3 => 'رنگ‌های مرتبط', 4 => 'سازگاری رنگ‌ها', 5 => 'بررسی نهایی'];
    @endphp

    {{-- Header --}}
    <x-admin.page-header
        title="تعریف و ویرایش طرح کارت"
        description="مراحل پنج‌گانه ثبت اطلاعات پایه، بارگذاری تصاویر چندرنگ، تفکیک رنگ‌ها و بررسی نهایی طرح"
    >
        <x-slot:actions>
            <a href="{{ route('admin.designs') }}" class="admin-btn admin-btn-secondary">
                <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
                <span>بازگشت به فهرست طرح‌ها</span>
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Step indicator --}}
    <div class="admin-card p-4 mb-6 overflow-x-auto">
        <ol class="flex items-center justify-between gap-3 min-w-[620px] px-2">
            @foreach($stepLabels as $number => $label)
                @php
                    $isCurrent = $step === $number;
                    $isDone = $step > $number;
                @endphp
                <li class="flex items-center gap-3 flex-1 last:flex-none">
                    <div class="flex items-center gap-2.5">
                        <span aria-hidden="true"
                              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold transition {{ $isCurrent ? 'bg-[#ffde5b] text-[#010619] shadow-md shadow-[#ffde5b]/30' : ($isDone ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400') }}">
                            @if($isDone)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $number }}
                            @endif
                        </span>
                        <div>
                            <span class="text-xs block {{ $isCurrent ? 'font-bold text-[#010619]' : ($isDone ? 'font-medium text-slate-800' : 'text-slate-400') }}">{{ $label }}</span>
                            @if($step === $number)
                                <span class="sr-only">مرحله فعلی</span>
                            @endif
                        </div>
                    </div>
                    @if($number < 5)
                        <div class="flex-1 mx-2">
                            <div class="h-0.5 w-full {{ $isDone ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    @if(session()->has('success'))
        <div class="mb-6 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <x-icons.check-badge class="mt-0.5 shrink-0 text-emerald-600" />
            <div class="min-w-0">{{ session('success') }}</div>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl mb-6 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Step ① اطلاعات --}}
    @if($step === 1)
        <div class="admin-card p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="admin-label">دسته‌بندی طرح</label>
                    <select wire:model="cateDesignId" class="admin-input">
                        <option value="">— انتخاب دسته‌بندی —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (غیرفعال)' }}</option>
                        @endforeach
                    </select>
                    @error('cateDesignId') <p class="admin-error">{{ $message }}</p> @enderror

                    <button type="button" wire:click="$toggle('showCategoryForm')" class="mt-2 text-xs font-bold text-[#010619] hover:underline transition">
                        {{ $showCategoryForm ? 'بستن فرم دسته‌بندی جدید' : '+ دسته‌بندی جدید' }}
                    </button>

                    @if($showCategoryForm)
                        <div class="mt-3 flex flex-wrap items-center gap-2 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                            <input type="text" wire:model="newCategoryName" placeholder="نام دسته‌بندی جدید"
                                   class="admin-input sm:w-56 text-xs">
                            <button type="button" wire:click="addCategory" class="admin-btn admin-btn-primary admin-btn-sm">
                                افزودن
                            </button>
                        </div>
                        @error('newCategoryName') <p class="admin-error">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label class="admin-label">نام طرح</label>
                    <input type="text" wire:model="name" class="admin-input" placeholder="مثال: عقاب طلایی">
                    @error('name') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="admin-label">توضیحات</label>
                <textarea wire:model="description" rows="3" class="admin-input" placeholder="توضیحات معرفی طرح"></textarea>
                @error('description') <p class="admin-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="admin-label">ترتیب نمایش</label>
                    <input type="number" wire:model="sortOrder" min="0" class="admin-input">
                    @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6 pt-2">
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                    <label for="is_active" class="text-sm font-medium text-slate-700">فعال (نمایش در سفارشی‌ساز)</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                    <label for="robots_index" class="text-sm font-medium text-slate-700">قابل ایندکس در موتورهای جستجو</label>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h4 class="font-bold text-slate-900 mb-3 text-sm">تنظیمات سئو</h4>
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
                    <div class="sm:col-span-2">
                        <label class="admin-label">آدرس Canonical</label>
                        <input type="text" wire:model="canonicalUrl" dir="ltr" class="admin-input font-mono text-xs">
                        @error('canonicalUrl') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="admin-label">محتوی سئو</label>
                    <textarea wire:model="seoContent" rows="3" class="admin-input"></textarea>
                    @error('seoContent') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    @endif

    {{-- Step ② تصاویر --}}
    @if($step === 2)
        @if(! $designId)
            <div class="admin-card p-8">
                <x-admin.empty-state
                    title="ابتدا اطلاعات پایه را ذخیره کنید"
                    description="قبل از بارگذاری تصویر، نام و دسته‌بندی طرح را در مرحله اول مشخص نمایید."
                />
            </div>
        @else
            <div class="space-y-6">
                <div class="admin-card p-6">
                    <h3 class="font-bold text-slate-900 mb-4">{{ $editingImageId ? 'ویرایش تصویر' : 'افزودن تصویر جدید' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="admin-label">رنگ تصویر</label>
                            <select wire:model="colorId" class="admin-input">
                                <option value="">— انتخاب رنگ —</option>
                                @foreach($colors as $color)
                                    <option value="{{ $color->id }}">{{ $color->name }}</option>
                                @endforeach
                            </select>
                            @error('colorId') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">مسیر تصویر</label>
                            <input type="text" wire:model="imagePath" placeholder="designs/eagle-black.png" dir="ltr" class="admin-input font-mono text-xs">
                            @error('imagePath') <p class="admin-error">{{ $message }}</p> @enderror
                            <input type="file" wire:model="imageUpload" accept="image/*" class="block w-full mt-2 text-sm text-slate-600 file:me-3 file:rounded-xl file:border-0 file:bg-slate-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-700 transition file:cursor-pointer">
                            @error('imageUpload') <p class="admin-error">{{ $message }}</p> @enderror
                            @if($imageUpload)
                                <img src="{{ $imageUpload->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-xl border border-slate-200" alt="پیش‌نمایش">
                            @elseif($editingImageId && $imagePath)
                                <img src="{{ asset('storage/'.$imagePath) }}" class="mt-2 h-24 w-24 object-cover rounded-xl border border-slate-200" alt="تصویر فعلی">
                            @endif
                        </div>
                        <div>
                            <label class="admin-label">متن جایگزین (Alt)</label>
                            <input type="text" wire:model="altText" class="admin-input">
                            @error('altText') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">عنوان تصویر</label>
                            <input type="text" wire:model="imageTitle" class="admin-input">
                            @error('imageTitle') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">فایل بهینه‌شده</label>
                            <input type="text" wire:model="optimizedFilename" placeholder="eagle-black-optimized.webp" dir="ltr" class="admin-input font-mono text-xs">
                            @error('optimizedFilename') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">ترتیب نمایش</label>
                            <input type="number" wire:model="imageSortOrder" min="0" class="admin-input">
                            @error('imageSortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="admin-label">کپشن سئو</label>
                        <textarea wire:model="seoCaption" rows="2" class="admin-input"></textarea>
                        @error('seoCaption') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <input type="checkbox" wire:model="imageIsActive" id="image_is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                        <label for="image_is_active" class="text-sm font-medium text-slate-700">فعال (نمایش در سفارشی‌ساز)</label>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <button type="button" wire:click="saveImage" class="admin-btn admin-btn-primary">
                            <span>ذخیره تصویر</span>
                        </button>
                        @if($editingImageId)
                            <button type="button" wire:click="resetImageForm" class="admin-btn admin-btn-secondary">
                                <span>انصراف از ویرایش</span>
                            </button>
                        @endif
                    </div>
                </div>

                @if($images->isEmpty())
                    <div class="admin-card p-8">
                        <x-admin.empty-state
                            title="هنوز تصویری ثبت نشده است"
                            description="برای این طرح، حداقل یک تصویر با مشخص کردن رنگ آن ثبت نمایید."
                        />
                    </div>
                @else
                    <div class="admin-card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50/70 border-b border-slate-100">
                                    <tr>
                                        <th class="admin-th w-16">#</th>
                                        <th class="admin-th">رنگ</th>
                                        <th class="admin-th">مسیر</th>
                                        <th class="admin-th">ترتیب</th>
                                        <th class="admin-th">وضعیت</th>
                                        <th class="admin-th text-center">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($images as $image)
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="admin-td text-slate-400 font-mono text-xs">{{ $image->id }}</td>
                                            <td class="admin-td">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-4 h-4 rounded-full border border-slate-200 shadow-sm shrink-0" style="background-color: {{ $image->color?->code_hex }}"></span>
                                                    <span class="text-xs text-slate-700">{{ $image->color?->name }}</span>
                                                </div>
                                            </td>
                                            <td class="admin-td text-slate-500 font-mono text-xs break-all" dir="ltr">{{ $image->image_path }}</td>
                                            <td class="admin-td text-slate-500 font-mono text-xs">{{ $image->sort_order }}</td>
                                            <td class="admin-td">
                                                <span class="admin-badge {{ $image->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                                                    {{ $image->is_active ? 'فعال' : 'غیرفعال' }}
                                                </span>
                                            </td>
                                            <td class="admin-td text-center">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <button type="button" wire:click="editImage({{ $image->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                                    <button type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="آیا از حذف این تصویر (به همراه سازگاری‌هایش) مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Step ③ رنگ‌ها --}}
    @if($step === 3)
        @if($images->isEmpty())
            <div class="admin-card p-8">
                <x-admin.empty-state
                    title="این طرح هنوز تصویری ندارد"
                    description="ابتدا در مرحله «تصاویر طرح» حداقل یک تصویر اضافه کنید."
                />
            </div>
        @else
            <div class="admin-card p-6">
                <h3 class="font-bold text-slate-900 mb-2">رنگ‌های مرتبط با طرح</h3>
                <p class="text-xs text-slate-500 mb-6">رنگ‌هایی که برای این طرح تصویر ثبت کرده‌اید. در مرحله بعد، سازگاری هر تصویر با رنگ‌های پایه کارت را تعیین کنید.</p>

                @if(empty($colorOverview))
                    <div class="text-center py-6 text-slate-400 text-sm">رنگی یافت نشد.</div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($colorOverview as $color)
                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 bg-slate-50/50">
                                <div class="w-9 h-9 rounded-xl border border-slate-300 shadow-sm shrink-0" style="background-color: {{ $color['hex'] }}"></div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm">{{ $color['name'] }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $color['count'] }} تصویر متصل</div>
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
            <div class="admin-card p-8">
                <x-admin.empty-state
                    title="این طرح تصویری ندارد"
                    description="ابتدا در مرحله «تصاویر طرح» حداقل یک تصویر اضافه کنید."
                />
            </div>
        @else
            <div class="space-y-6">
                <div class="admin-card p-5">
                    <p class="text-xs text-slate-500 mb-4">برای هر تصویر طرح، سازگاری با رنگ‌های کارت را با کلیک روی گزینه‌ها مشخص کنید. فقط ترکیب‌های مجاز در سفارشی‌ساز نمایش داده می‌شوند.</p>
                    @foreach($compatibilityRows as $row)
                        <div class="rounded-xl border border-slate-200 p-5 mb-4 bg-slate-50/30">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-xs font-mono break-all" dir="ltr">{{ $row['image']->image_path }}</h4>
                                    <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-2">
                                        <span>رنگ تصویر:</span>
                                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-700">
                                            <span class="w-3.5 h-3.5 rounded-full border border-slate-200 inline-block shadow-sm" style="background-color: {{ $row['image']->color?->code_hex }}"></span>
                                            {{ $row['image']->color?->name }}
                                        </span>
                                        <span class="text-slate-300">|</span>
                                        <span class="admin-badge admin-badge-neutral text-[11px]">{{ $row['allowedCount'] }} از {{ count($colors) }} رنگ مجاز</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2.5 pt-2 border-t border-slate-100">
                                @foreach($colors as $color)
                                    @php $allowed = $row['map'][$color->id] ?? false; @endphp
                                    <button
                                        type="button"
                                        wire:click="toggleCompatibility({{ $row['image']->id }}, {{ $color->id }})"
                                        class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-medium transition cursor-pointer {{ $allowed ? 'border-emerald-300 bg-emerald-50 text-emerald-800 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}"
                                    >
                                        <input type="checkbox" {{ $allowed ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 pointer-events-none">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-3.5 h-3.5 rounded-full border border-slate-200 inline-block shadow-sm" style="background-color: {{ $color->code_hex }}"></span>
                                            <span>{{ $color->name }}</span>
                                        </span>
                                        <span class="text-[11px] {{ $allowed ? 'text-emerald-700 font-bold' : 'text-slate-400' }}">{{ $allowed ? 'مجاز' : 'غیرمجاز' }}</span>
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
        <div class="admin-card p-6">
            <h3 class="font-bold text-slate-900 mb-6 text-base">بررسی نهایی مشخصات طرح</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">نام طرح</dt>
                    <dd class="font-bold text-slate-900 text-end">{{ $summary['name'] ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">دسته‌بندی</dt>
                    <dd class="font-medium text-slate-900 text-end">{{ $summary['category'] ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">اسلاگ</dt>
                    <dd class="font-medium text-slate-900 font-mono text-xs text-end" dir="ltr">{{ $summary['slug'] ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">وضعیت</dt>
                    <dd class="font-medium text-end">
                        <span class="admin-badge {{ $summary['isActive'] ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                            {{ $summary['isActive'] ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">ترتیب نمایش</dt>
                    <dd class="font-mono text-xs text-slate-900 text-end">{{ $summary['sortOrder'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">تصاویر ثبت‌شده</dt>
                    <dd class="font-bold text-slate-900 text-end">{{ $summary['imagesCount'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">رنگ‌های مرتبط</dt>
                    <dd class="font-bold text-slate-900 text-end">{{ $summary['colorsCount'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 pb-3">
                    <dt class="text-slate-500">ترکیبات مجاز (تصویر × رنگ)</dt>
                    <dd class="font-bold text-[#010619] text-end">{{ $summary['allowedCombos'] }}</dd>
                </div>
            </dl>
            @if($summary['description'])
                <div class="mt-6 border-t border-slate-100 pt-4">
                    <h4 class="font-bold text-slate-900 text-sm mb-2">توضیحات</h4>
                    <p class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">{{ $summary['description'] }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Wizard footer navigation --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
        <button type="button"
                wire:click="back"
                @if($step === 1) disabled @endif
                class="admin-btn admin-btn-secondary {{ $step === 1 ? 'opacity-40 cursor-not-allowed pointer-events-none' : '' }}">
            <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
            <span>مرحله قبل</span>
        </button>

        @if($step < 5)
            <button type="button"
                    wire:click="next"
                    class="admin-btn admin-btn-primary">
                <span>مرحله بعد</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </button>
        @else
            <button type="button"
                    wire:click="save"
                    class="admin-btn admin-btn-primary bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>ذخیره طرح</span>
            </button>
        @endif
    </div>
</div>