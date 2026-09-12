<section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
    <h3 class="mb-3 text-lg font-semibold text-gray-900">رنگ‌ها و قیمت‌ها</h3>

    <div class="grid gap-2">
        @forelse($colorPrices as $item)
            <a
                href="{{ route('catalog.products.show', ['slug' => $product->slug, 'color_id' => $item->color_id]) }}"
                class="flex items-center justify-between rounded border px-3 py-2 text-sm transition {{ (int) $selectedColorId === (int) $item->color_id ? 'border-primary-600 bg-primary-50' : 'border-gray-200 hover:border-gray-300' }}"
            >
                <span>{{ $item->color?->name ?? 'ناموجود' }}</span>
                <span class="font-semibold">{{ number_format($item->price) }} تومان</span>
            </a>
        @empty
            <p class="text-sm text-gray-500">قیمت‌گذاری رنگی فعالی یافت نشد.</p>
        @endforelse
    </div>
</section>