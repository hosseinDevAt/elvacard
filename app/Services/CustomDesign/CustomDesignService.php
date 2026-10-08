<?php

namespace App\Services\CustomDesign;

use App\Enums\DesignImageLayoutModeEnum;
use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomDesignService
{
    public const DISK = 'local';
    public const TEMP_DIRECTORY = 'custom_designs/temp';
    public const ORDERS_DIRECTORY = 'custom_designs/orders';

    public const MAX_FILE_SIZE_BYTES = 5242880; // 5 MB (5120 KB)
    public const MIN_DIMENSION = 300;
    public const MAX_DIMENSION = 4000;

    public const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];
    public const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    /**
     * Validate an uploaded custom design file, store it securely in private storage,
     * and bind ownership metadata to the customer's current session.
     *
     * @throws ValidationException
     */
    public function validateAndStoreTemp(UploadedFile $file, string $workflow): array
    {
        $this->validateFile($file);

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'png';
        }

        $imageSizeInfo = @getimagesize($file->getRealPath());
        $width = is_array($imageSizeInfo) ? (int) ($imageSizeInfo[0] ?? 0) : 0;
        $height = is_array($imageSizeInfo) ? (int) ($imageSizeInfo[1] ?? 0) : 0;

        $token = Str::random(40);
        $fileName = "{$token}.{$extension}";
        $tempPath = self::TEMP_DIRECTORY . "/{$fileName}";

        $disk = Storage::disk(self::DISK);
        $stored = $disk->put($tempPath, file_get_contents($file->getRealPath()));

        if (! $stored) {
            throw ValidationException::withMessages([
                'customImage' => 'خطا در ذخیره‌سازی موقت تصویر. لطفاً دوباره تلاش کنید.',
            ]);
        }

        $metadata = [
            'token' => $token,
            'path' => $tempPath,
            'disk' => self::DISK,
            'original_name' => substr((string) $file->getClientOriginalName(), 0, 120),
            'extension' => $extension,
            'mime_type' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'width' => $width,
            'height' => $height,
            'workflow' => $workflow,
            'created_at' => now()->timestamp,
        ];

        session()->put("custom_designs.{$token}", $metadata);

        return $metadata;
    }

    /**
     * Strict server-side validation against malicious, non-image, oversized,
     * or dimension-out-of-bound files.
     *
     * @throws ValidationException
     */
    public function validateFile(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'customImage' => 'فایل آپلود شده معتبر نیست یا فرآیند آپلود با خطا مواجه شده است.',
            ]);
        }

        // File size check: max 5 MB
        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'customImage' => 'حجم فایل نباید بیشتر از ۵ مگابایت باشد.',
            ]);
        }

        // Extension check
        $clientExtension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($clientExtension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'customImage' => 'فرمت فایل انتخابی مجاز نیست. فقط فرمت‌های PNG، JPG و WEBP پذیرفته می‌شوند.',
            ]);
        }

        // MIME check from UploadedFile
        $mime = strtolower((string) $file->getMimeType());
        if (! in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'customImage' => 'نوع محتوای فایل معتبر نیست. لطفاً یک تصویر واقعی با فرمت PNG، JPG یا WEBP ارسال کنید.',
            ]);
        }

        // Real image content inspection via getimagesize
        $realPath = $file->getRealPath();
        if (! $realPath || ! file_exists($realPath)) {
            throw ValidationException::withMessages([
                'customImage' => 'فایل آپلود شده در سرور در دسترس نیست.',
            ]);
        }

        $imageInfo = @getimagesize($realPath);
        if ($imageInfo === false || ! is_array($imageInfo)) {
            throw ValidationException::withMessages([
                'customImage' => 'فایل ارسال شده تصویر معتبر نیست.',
            ]);
        }

        $imageType = $imageInfo[2] ?? null;
        $validImageTypes = [IMAGETYPE_PNG, IMAGETYPE_JPEG];
        if (defined('IMAGETYPE_WEBP')) {
            $validImageTypes[] = IMAGETYPE_WEBP;
        }

        if (! in_array($imageType, $validImageTypes, true)) {
            throw ValidationException::withMessages([
                'customImage' => 'فرمت داخلی تصویر با فرمت مجاز تطابق ندارد.',
            ]);
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);

        if ($width < self::MIN_DIMENSION || $height < self::MIN_DIMENSION) {
            throw ValidationException::withMessages([
                'customImage' => 'ابعاد تصویر باید حداقل ۳۰۰ در ۳۰۰ پیکسل باشد تا کیفیت چاپ حفظ شود.',
            ]);
        }

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw ValidationException::withMessages([
                'customImage' => 'ابعاد تصویر نباید بیشتر از ۴۰۰۰ در ۴۰۰۰ پیکسل باشد.',
            ]);
        }

        // SVG and script injection check (SVG is strictly forbidden in this phase)
        $buffer = @file_get_contents($realPath, false, null, 0, 4096);
        if ($buffer !== false) {
            $lowerBuffer = strtolower($buffer);
            if (
                str_contains($lowerBuffer, '<svg') ||
                str_contains($lowerBuffer, '<?xml') ||
                str_contains($lowerBuffer, '<script') ||
                str_contains($lowerBuffer, 'xmlns="http://www.w3.org/2000/svg"')
            ) {
                throw ValidationException::withMessages([
                    'customImage' => 'فرمت‌های وکتور یا SVG مجاز نمی‌باشند. لطفاً فایل تصویری PNG، JPG یا WEBP بارگذاری کنید.',
                ]);
            }
        }
    }

    /**
     * Retrieve a validated temporary custom design from the current session.
     */
    public function getTempDesign(?string $token): ?array
    {
        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{40,64}$/', $token)) {
            return null;
        }

        $stored = session("custom_designs.{$token}");
        if (! is_array($stored) || empty($stored['path'])) {
            return null;
        }

        $disk = Storage::disk(self::DISK);
        if (! $disk->exists($stored['path'])) {
            session()->forget("custom_designs.{$token}");
            return null;
        }

        return $stored;
    }

    /**
     * Remove temporary custom design from storage and current session.
     */
    public function removeTempDesign(?string $token): void
    {
        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{40,64}$/', $token)) {
            return;
        }

        $stored = session("custom_designs.{$token}");
        if (is_array($stored) && ! empty($stored['path'])) {
            Storage::disk(self::DISK)->delete($stored['path']);
        }

        session()->forget("custom_designs.{$token}");
    }

    /**
     * Permanently snapshot a temporary custom design to the order storage directory.
     * Returns metadata for storing on the OrderItem.
     *
     * @throws \RuntimeException
     */
    public function snapshotToOrder(string $token, Order $order, string $itemUuid, string $layoutMode = 'centered'): array
    {
        $tempData = $this->getTempDesign($token);
        if (! $tempData) {
            throw new \RuntimeException('طرح اختصاصی موقت یافت نشد یا منقضی شده است.');
        }

        $extension = $tempData['extension'] ?? 'png';
        $safeRef = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $order->reference);
        $safeUuid = preg_replace('/[^A-Za-z0-9_-]/', '', $itemUuid);
        $destFileName = "{$safeRef}_{$safeUuid}.{$extension}";
        $destPath = self::ORDERS_DIRECTORY . "/{$destFileName}";

        $disk = Storage::disk(self::DISK);

        // Ensure orders directory exists
        if (! $disk->exists(self::ORDERS_DIRECTORY)) {
            $disk->makeDirectory(self::ORDERS_DIRECTORY);
        }

        $copied = $disk->copy($tempData['path'], $destPath);
        if (! $copied) {
            throw new \RuntimeException('خطا در ثبت و آرشیو طرح اختصاصی سفارش.');
        }

        $validLayoutMode = in_array($layoutMode, [
            DesignImageLayoutModeEnum::CENTERED->value,
            DesignImageLayoutModeEnum::FULL_CARD->value,
        ], true) ? $layoutMode : DesignImageLayoutModeEnum::CENTERED->value;

        return [
            'design_image_path_snapshot' => $destPath,
            'layout_mode_snapshot' => $validLayoutMode,
            'metadata' => [
                'is_custom_design' => true,
                'original_name' => $tempData['original_name'] ?? 'custom-design.png',
                'mime_type' => $tempData['mime_type'] ?? 'image/png',
                'size' => $tempData['size'] ?? 0,
                'width' => $tempData['width'] ?? 0,
                'height' => $tempData['height'] ?? 0,
                'workflow' => $tempData['workflow'] ?? null,
                'token' => $token,
            ],
        ];
    }

    /**
     * Prune stale temp upload files older than given hours (default 24h).
     */
    public function pruneStaleTemp(int $hours = 24): int
    {
        $disk = Storage::disk(self::DISK);
        if (! $disk->exists(self::TEMP_DIRECTORY)) {
            return 0;
        }

        $files = $disk->files(self::TEMP_DIRECTORY);
        $threshold = now()->subHours($hours)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            try {
                $lastModified = $disk->lastModified($file);
                if ($lastModified <= $threshold) {
                    $disk->delete($file);
                    $deleted++;
                }
            } catch (\Throwable) {
                // Continue pruning other files even if one fails
            }
        }

        return $deleted;
    }
}
