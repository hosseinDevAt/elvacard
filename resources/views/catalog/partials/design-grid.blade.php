<section class="space-y-6">
    @forelse($catalog as $category)
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ $category->name }}</h3>

            @if($category->designs->isEmpty())
                <p class="text-sm text-gray-500">طرح فعالی در این دسته‌بندی وجود ندارد.</p>
            @else
                <div class="space-y-4">
                    @foreach($category->designs as $design)
                        <div class="rounded border border-gray-100 p-3">
                            <h4 class="font-medium text-gray-900">{{ $design->name }}</h4>

                            <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @forelse($design->images as $image)
                                    <div class="rounded border border-gray-200 p-2 text-sm">
                                        <p class="font-medium">{{ $image->color?->name ?? 'ناموجود' }}</p>
                                        <p class="text-xs text-gray-500 break-all">{{ $image->image_path }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">تصویر فعالی وجود ندارد.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-500 shadow-sm">
            داده‌ای برای فهرست طرح‌ها یافت نشد.
        </div>
    @endforelse
</section>