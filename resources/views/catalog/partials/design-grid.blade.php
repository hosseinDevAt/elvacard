<section class="space-y-4">
    @forelse($catalog as $design)
        <div
            id="design-{{ $design['id'] }}"
            class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm scroll-mt-24"
        >
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded border border-gray-100 p-2">
                    @if ($design['preview_image_path'])
                        <img
                            src="{{ asset('storage/' . $design['preview_image_path']) }}"
                            alt="{{ $design['name'] }}"
                            class="mx-auto max-h-40 object-contain"
                        >
                    @else
                        <p class="text-sm text-gray-500">تصویر فعالی وجود ندارد.</p>
                    @endif
                </div>
                <div class="sm:col-span-1 lg:col-span-2 flex flex-col justify-center">
                    <h4 class="font-medium text-gray-900">{{ $design['name'] }}</h4>
                    <p class="mt-1 text-xs text-gray-500 break-all">{{ $design['preview_image_path'] }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-500 shadow-sm">
            داده‌ای برای فهرست طرح‌ها یافت نشد.
        </div>
    @endforelse

    @if ($catalog->hasPages())
        <div class="pt-1">
            {{ $catalog->links() }}
        </div>
    @endif
</section>