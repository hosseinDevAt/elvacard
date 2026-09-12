<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PaymentReceiptController extends Controller
{
    public function show(Request $request, Order $order, Payment $payment): Response
    {
        if ((int) $payment->order_id !== (int) $order->id) {
            abort(404);
        }

        $path = $payment->receipt_path;

        if (! is_string($path) || $path === '') {
            abort(404);
        }

        $disk = Storage::disk('local');
        $rootReal = realpath($disk->path(''));

        if ($rootReal === false) {
            abort(404);
        }

        $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $fileReal = realpath($disk->path($path));

        if ($fileReal === false || ! str_starts_with($fileReal, $rootReal)) {
            abort(404);
        }

        if (! $disk->exists($path)) {
            abort(404);
        }

        $name = 'receipt-'.$order->reference;

        if ($request->boolean('download')) {
            return $disk->download($path, $name);
        }

        return $disk->response($path, $name);
    }
}