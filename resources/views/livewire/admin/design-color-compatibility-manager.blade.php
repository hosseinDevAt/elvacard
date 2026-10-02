<div>
    <x-admin.page-header
        title="محدودیت‌های رنگی و سازگاری طرح‌ها"
        description="تعیین رنگ‌های مجاز پایه کارت برای هر تصویر طرح، جهت تفکیک در میزکار طراحی و سفارشی‌ساز"
    />

    <div class="admin-card p-6 mb-6">
        <label class="admin-label mb-2">انتخاب طرح جهت تنظیم سازگاری</label>
        <select wire:model.live="designFilter" class="admin-input sm:w-80">
            <option value="">— انتخاب طرح —</option>
            @foreach($designOptions as $design)
                <option value="{{ $design->id }}">{{ $design->name }}</option>
            @endforeach
        </select>
        @error('designFilter')
            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-slate-400 mt-2">برای هر تصویر طرح، سازگاری با هر رنگ کارت را با کلیک روی دکمه‌ها مشخص کنید. فقط ترکیب‌های مجاز در میزکار طراحی نمایش داده می‌شوند.</p>
    </div>

    @if(! $designFilter)
        <div class="admin-card p-8">
            <x-admin.empty-state
                title="طرحی انتخاب نشده است"
                description="برای مشاهده و تنظیم سازگاری‌ها، ابتدا یک طرح را از منوی بالا انتخاب کنید."
            />
        </div>
    @elseif(empty($images))
        <div class="admin-card p-8">
            <x-admin.empty-state
                title="این طرح تصویری ندارد"
                description="برای این طرح هنوز تصویری ثبت نشده است. ابتدا در بخش «تصاویر طرح‌ها» تصویر اضافه کنید."
            />
        </div>
    @else
        <div class="space-y-6">
            @foreach($images as $row)
                <div class="admin-card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-slate-900 font-mono text-xs break-all" dir="ltr">{{ $row['image']->image_path }}</h3>
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
                                wire:click="toggle({{ $row['image']->id }}, {{ $color->id }})"
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
    @endif
</div>