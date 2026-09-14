<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            جزئیات سفارش {{ $order->reference ?? ('#'.$order->id) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات سفارش</h3>

                <div class="grid gap-2 text-sm text-gray-700 sm:grid-cols-2">
                    <p><span class="font-semibold">شماره سفارش:</span> {{ $order->reference ?? ('#'.$order->id) }}</p>
                    <p><span class="font-semibold">تاریخ ثبت:</span> {{ $order->created_at?->format('Y-m-d H:i') }}</p>
                    <p><span class="font-semibold">وضعیت سفارش:</span> {{ $order->status?->faLabel() ?? $order->status }}</p>
                    <p><span class="font-semibold">وضعیت پرداخت:</span> {{ $order->payment_status?->faLabel() ?? $order->payment_status }}</p>
                    <p><span class="font-semibold">نام مشتری:</span> {{ $order->customer_name }}</p>
                    <p><span class="font-semibold">شماره تماس:</span> <span dir="ltr">{{ $order->customer_phone }}</span></p>
                    <p class="break-words sm:col-span-2"><span class="font-semibold">آدرس ارسال:</span> {{ $order->shipping_address ?? '-' }}</p>
                    <p><span class="font-semibold">کد پستی:</span> <span dir="ltr">{{ $order->shipping_postal_code ?? '-' }}</span></p>
                    <p><span class="font-semibold">پلاک:</span> {{ $order->shipping_plaque ?? '-' }}</p>
                    @if ($order->shipping_description)
                        <p class="break-words sm:col-span-2"><span class="font-semibold">توضیح آدرس:</span> {{ $order->shipping_description }}</p>
                    @endif
                    <p><span class="font-semibold">مبلغ کل:</span> {{ number_format($order->total_price) }} تومان</p>
                    <p class="break-words"><span class="font-semibold">توضیحات:</span> {{ $order->notes ?? '-' }}</p>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">آیتم‌های سفارش</h3>

                <div class="space-y-4">
                    @forelse ($order->items as $item)
                        @php
                            $customization = is_array($item->customization_json) ? $item->customization_json : json_decode($item->customization_json, true);
                            $itemWorkflow = $item->customization_workflow;
                            if ($itemWorkflow === null) {
                                $itemWorkflow = \App\Services\Customization\CustomizationWorkflowRegistry::classifyLegacyCustomization(is_array($customization) ? $customization : []);
                            }
                            $isFuel = $itemWorkflow === \App\Enums\CustomizationWorkflowEnum::FUEL_CARD;
                        @endphp
                        <div class="rounded border border-gray-100 p-4 text-sm">
                            @if ($isFuel)
                                <p><span class="font-semibold">روش شخصی‌سازی:</span> {{ $itemWorkflow->faLabel() }}</p>
                            @endif
                            <p class="break-words"><span class="font-semibold">محصول:</span> {{ $item->product_name_snapshot }}</p>
                            <p><span class="font-semibold">تعداد:</span> {{ $item->quantity }}</p>
                            <p><span class="font-semibold">قیمت واحد:</span> {{ number_format($item->unit_price_snapshot) }} تومان</p>
                            <p><span class="font-semibold">مبلغ نهایی:</span> {{ number_format($item->final_price) }} تومان</p>
                            <p><span class="font-semibold">رنگ کارت:</span> {{ $item->color_name_snapshot ?? 'ثبت نشده' }}</p>
                            <p><span class="font-semibold">طرح کارت:</span> {{ $item->design_name_snapshot ?? 'ثبت نشده' }}</p>

                            @if ($isFuel && $item->design_image_path_snapshot)
                                <p><span class="font-semibold">تصویر طرح:</span> {{ $item->design_image_path_snapshot }}</p>
                            @endif

                            @if (! $isFuel && $item->customization_json)
                                <div class="mt-2 border-t border-gray-100 pt-2 text-gray-600">
                                    @if (is_array($customization))
                                        <div class="grid gap-1.5 sm:grid-cols-2 text-sm">
                                            @if (! empty($customization['card_number']))
                                                <p class="sm:col-span-2"><span class="font-semibold">شماره کارت:</span> <span dir="ltr" class="font-mono"><span style="direction: ltr; unicode-bidi: isolate;">{{ \App\Services\Customization\CardPresenter::presentCardNumber($customization['card_number']) }}</span></span></p>
                                            @endif
                                            @if (! empty($customization['card_holder_name']))
                                                <p><span class="font-semibold">نام دارنده کارت:</span> {{ $customization['card_holder_name'] }}</p>
                                            @endif
                                            @if (! empty($customization['back_text']))
                                                <p><span class="font-semibold">متن پشت کارت:</span> {{ $customization['back_text'] }}</p>
                                            @endif
                                            @if (! empty($customization['security_cvv_enabled']))
                                                <p><span class="font-semibold">مقدار CVV2:</span> <span dir="ltr">{{ $customization['cvv2'] ?? 'ثبت نشده' }}</span></p>
                                            @endif
                                            @if (! empty($customization['security_expiry_enabled']))
                                                <p><span class="font-semibold">تاریخ انقضا:</span> <span dir="ltr">{{ $customization['expiry_month'] ?? '--' }}/{{ $customization['expiry_year'] ?? '--' }}</span></p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-400">آیتمی برای این سفارش ثبت نشده است.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">تاریخچه پرداخت</h3>

                @forelse ($order->payments as $payment)
                    <div class="rounded border border-gray-100 p-4 text-sm mb-3">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <p><span class="font-semibold">روش:</span> {{ $payment->method->faLabel() }}</p>
                            <p><span class="font-semibold">وضعیت:</span> {{ $payment->status->faLabel() }}</p>
                            <p><span class="font-semibold">مبلغ:</span> {{ number_format($payment->amount) }} تومان</p>
                            <p><span class="font-semibold">کد پیگیری:</span> <span dir="ltr" class="break-all">{{ $payment->tracking_code ?? '-' }}</span></p>
                            <p class="break-words"><span class="font-semibold">توضیحات:</span> {{ $payment->metadata['note'] ?? '-' }}</p>
                            <p><span class="font-semibold">تاریخ:</span> {{ $payment->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">پرداختی ثبت نشده است.</p>
                @endforelse

                @if ($canRetryPayment)
                    <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="mt-3 inline-block rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">پرداخت مجدد</a>
                @endif
            </div>

            <div>
                <a href="{{ route('orders.index') }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm transition">بازگشت به سفارش‌های من</a>
            </div>
        </div>
    </div>
</x-app-layout>