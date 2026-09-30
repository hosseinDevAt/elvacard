<div>
    

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">محدودیت‌های رنگی و سازگاری طرح‌ها</h2>
            <p class="text-xs text-slate-500 mt-1">تعیین رنگ‌هایی که با طرح یا رنگ پایه کارت سازگار نیستند و در میزکار غیرفعال می‌شوند</p>
        </div>
    </div>

    <div class="admin-card p-6 mb-6">
        <label class="admin-label mb-2">انتخاب طرح</label>
        <select wire:model.live="designFilter" class="admin-input sm:w-80">
            <option value="">— انتخاب طرح —</option>
            @foreach($designOptions as $design)
                <option value="{{ $design->id }}">{{ $design->name }}</option>
            @endforeach
        </select>
        <p class="text-xs text-slate-400 mt-2">برای هر تصویر طرح، سازگاری با هر رنگ کارت را با تیک مشخص کنید. فقط ترکیب‌های مجاز در میزکار طراحی نمایش داده می‌شوند.</p>
    </div>

    @if(! $designFilter)
        <div class="admin-card p-8 text-center text-gray-400">
            برای شروع، یک طرح را از باکس بالا انتخاب کنید.
        </div>
    @elseif(empty($images))
        <div class="admin-card p-8 text-center text-gray-400">
            این طرح تصویری ندارد. ابتدا در «تصاویر طرح‌ها» تصویر اضافه کنید.
        </div>
    @else
        <div class="space-y-6">
            @foreach($images as $row)
                <div class="admin-card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $row['image']->image_path }}</h3>
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
                                wire:click="toggle({{ $row['image']->id }}, {{ $color->id }})"
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
    @endif
</div>