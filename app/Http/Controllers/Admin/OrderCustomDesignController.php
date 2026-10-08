<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class OrderCustomDesignController extends Controller
{
    /**
     * Safely display or download an authorized customer custom design snapshot for an order item.
     */
    public function show(Request $request, Order $order, OrderItem $item): Response
    {
        if ((int) $item->order_id !== (int) $order->id) {
            abort(404);
        }

        $path = $item->design_image_path_snapshot;

        if (! is_string($path) || $path === '') {
            abort(404);
        }

        $disk = Storage::disk('local');
        $rootReal = realpath($disk->path(''));

        if ($rootReal === false) {
            abort(404);
        }

        $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $fileReal = realpath($disk->path($path));

        if ($fileReal === false || ! str_starts_with($fileReal, $rootReal)) {
            abort(404);
        }

        if (! $disk->exists($path)) {
            abort(404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'png';
        $filename = 'custom-design-' . ($order->reference ?: $order->id) . '-' . $item->id . '.' . $extension;

        if ($request->boolean('download')) {
            return $disk->download($path, $filename);
        }

        return $disk->response($path, $filename);
    }
}
