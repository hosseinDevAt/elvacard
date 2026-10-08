<?php

namespace App\Http\Controllers\CustomDesign;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CustomerCustomDesignPreviewController extends Controller
{
    /**
     * Safely serve a preview of a temporary custom design owned by the current session.
     */
    public function show(Request $request, string $token): Response
    {
        if (! preg_match('/^[A-Za-z0-9]{40,64}$/', $token)) {
            abort(404);
        }

        // Ownership check: must be stored in current session
        $info = session("custom_designs.{$token}");
        if (! is_array($info) || empty($info['path'])) {
            abort(404);
        }

        $path = (string) $info['path'];
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

        return $disk->response($path, 'preview-' . $token);
    }
}
