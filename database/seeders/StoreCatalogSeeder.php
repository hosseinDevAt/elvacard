<?php

namespace Database\Seeders;

use App\Enums\ProductTypeEnum;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Development data for the ordinary Store flow.
 *
 * Bank/Fuel card workflows are already covered by DatabaseSeeder. This seeder
 * adds two genuine, purchasable Standard (customization_workflow = null)
 * products so /catalog/products, the Product Gallery and the cart can be
 * verified end to end with real data.
 *
 * It is intentionally NOT called by DatabaseSeeder: the test suite seeds the
 * database assuming the ordinary Store has no products (the Store/Custom
 * Design boundary), so this is a manual, local-only dev seeder like
 * LocalTestAdminSeeder.
 *
 * Run with: php artisan db:seed --class=StoreCatalogSeeder
 */
class StoreCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') || config('app.env') === 'production') {
            throw new \RuntimeException('Refusing to seed development store data in production.');
        }

        $silver = $this->color('نقره‌ای', '#C0C0C0', 2);
        $gold = $this->color('طلایی', '#FFD700', 3);
        $black = $this->color('مشکی مات', '#1a1a1a', 1);

        $bracelet = Product::updateOrCreate(
            ['slug' => 'custom-steel-bracelet'],
            [
                'type' => ProductTypeEnum::STANDARD,
                'customization_workflow' => null,
                'name' => 'دستبند استیل سفارشی',
                'description' => 'دستبند استیل ضدزنگ با آبکاری مقاوم؛ طراحی مینیمال و بند قابل تنظیم، مناسب استفاده روزمره و هدیه.',
                'base_price' => 380000,
                'supports_chip_selection' => false,
                'is_active' => true,
                'meta_title' => 'خرید دستبند استیل سفارشی',
                'meta_description' => 'دستبند استیل ضدزنگ با آبکاری مقاوم و طراحی مینیمال؛ مناسب استفاده روزمره و هدیه.',
                'robots_index' => true,
            ]
        );

        $necklace = Product::updateOrCreate(
            ['slug' => 'custom-steel-necklace'],
            [
                'type' => ProductTypeEnum::STANDARD,
                'customization_workflow' => null,
                'name' => 'گردن‌آویز استیل سفارشی',
                'description' => 'گردن‌آویز استیل با زنجیر مقاوم و پلاک مینیمال؛ انتخابی ماندگار برای استایل روزمره و هدیه.',
                'base_price' => 520000,
                'supports_chip_selection' => false,
                'is_active' => true,
                'meta_title' => 'خرید گردن‌آویز استیل سفارشی',
                'meta_description' => 'گردن‌آویز استیل با زنجیر مقاوم و پلاک مینیمال؛ انتخابی ماندگار برای هدیه.',
                'robots_index' => true,
            ]
        );

        $braceletImages = [
            'products/bracelet-steel-1.png',
            'products/bracelet-steel-2.png',
        ];
        $necklaceImages = [
            'products/necklace-steel-1.png',
            'products/necklace-steel-2.png',
        ];

        $this->generateImage($braceletImages[0], 'bracelet', 'dark', 1);
        $this->generateImage($braceletImages[1], 'bracelet', 'light', 2);
        $this->generateImage($necklaceImages[0], 'necklace', 'light', 1);
        $this->generateImage($necklaceImages[1], 'necklace', 'dark', 2);

        $bracelet->forceFill(['main_image' => $braceletImages[0]])->save();
        $necklace->forceFill(['main_image' => $necklaceImages[0]])->save();

        $this->syncImages($bracelet, $braceletImages);
        $this->syncImages($necklace, $necklaceImages);

        $this->syncColorPrices($bracelet, [
            [$black->id, 360000],
            [$silver->id, 380000],
            [$gold->id, 460000],
        ]);

        $this->syncColorPrices($necklace, [
            [$black->id, 490000],
            [$silver->id, 520000],
            [$gold->id, 610000],
        ]);

        echo "\n[StoreCatalogSeeder] Store products ready\n";
        echo " - {$bracelet->name} (/{$bracelet->slug}) from ".number_format(360000)." تومان\n";
        echo " - {$necklace->name} (/{$necklace->slug}) from ".number_format(490000)." تومان\n";
    }

    private function color(string $name, string $hex, int $sortOrder): Color
    {
        return Color::firstOrCreate(
            ['name' => $name],
            ['code_hex' => $hex, 'is_active' => true, 'sort_order' => $sortOrder]
        );
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $prices
     */
    private function syncColorPrices(Product $product, array $prices): void
    {
        foreach ($prices as [$colorId, $price]) {
            ProductColorPrice::updateOrCreate(
                ['product_id' => $product->id, 'color_id' => $colorId],
                ['price' => $price, 'is_active' => true]
            );
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function syncImages(Product $product, array $paths): void
    {
        foreach ($paths as $index => $path) {
            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'color_id' => null, 'image_path' => $path],
                ['sort_order' => $index, 'is_primary' => $index === 0]
            );
        }
    }

    /**
     * Generate a realistic-enough product mockup on the public disk. The Store
     * only needs a valid image file per product; GD is guarded so a host
     * without it still seeds usable products (the gallery then shows its
     * placeholder until an image is uploaded through the admin panel).
     */
    private function generateImage(string $path, string $shape, string $theme, int $variant): void
    {
        $disk = Storage::disk('public');

        if ($disk->exists($path) || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        $themes = [
            'dark' => [
                'bg_top' => [20, 22, 27],
                'bg_bottom' => [58, 63, 72],
                'metal' => [203, 168, 84],
                'metal_light' => [255, 236, 176],
                'metal_dark' => [104, 80, 24],
                'plate' => [232, 224, 210],
            ],
            'light' => [
                'bg_top' => [246, 243, 236],
                'bg_bottom' => [214, 206, 192],
                'metal' => [176, 142, 66],
                'metal_light' => [255, 244, 205],
                'metal_dark' => [96, 72, 20],
                'plate' => [255, 255, 255],
            ],
        ];

        $palette = $themes[$theme] ?? $themes['dark'];

        $size = 1200;
        $canvas = imagecreatetruecolor($size, $size);

        if (function_exists('imageantialias')) {
            imageantialias($canvas, true);
        }

        for ($y = 0; $y < $size; $y++) {
            $ratio = $y / ($size - 1);
            $color = imagecolorallocate(
                $canvas,
                (int) round($palette['bg_top'][0] + ($palette['bg_bottom'][0] - $palette['bg_top'][0]) * $ratio),
                (int) round($palette['bg_top'][1] + ($palette['bg_bottom'][1] - $palette['bg_top'][1]) * $ratio),
                (int) round($palette['bg_top'][2] + ($palette['bg_bottom'][2] - $palette['bg_top'][2]) * $ratio),
            );
            imageline($canvas, 0, $y, $size, $y, $color);
        }

        $metal = imagecolorallocate($canvas, ...$palette['metal']);
        $light = imagecolorallocate($canvas, ...$palette['metal_light']);
        $dark = imagecolorallocate($canvas, ...$palette['metal_dark']);
        $plate = imagecolorallocate($canvas, ...$palette['plate']);

        if ($shape === 'bracelet') {
            $this->drawBracelet($canvas, $variant, $metal, $light, $dark, $plate);
        } else {
            $this->drawNecklace($canvas, $variant, $metal, $light, $dark, $plate);
        }

        ob_start();
        imagepng($canvas);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        $disk->put($path, $binary);
    }

    private function drawBracelet($canvas, int $variant, int $metal, int $light, int $dark, int $plate): void
    {
        $cx = 600;
        $cy = 600;
        $radius = $variant === 1 ? 330 : 395;
        $bead = $variant === 1 ? 36 : 43;
        $count = 22;

        for ($i = 0; $i < $count; $i++) {
            $angle = (2 * M_PI / $count) * $i;
            $x = $cx + (int) round($radius * cos($angle));
            $y = $cy + (int) round($radius * sin($angle));

            imagefilledellipse($canvas, $x + 4, $y + 6, $bead * 2, $bead * 2, $dark);
            imagefilledellipse($canvas, $x, $y, $bead * 2, $bead * 2, $metal);
            imagefilledellipse(
                $canvas,
                $x - (int) round($bead / 2.5),
                $y - (int) round($bead / 2.5),
                (int) round($bead * 0.85),
                (int) round($bead * 0.85),
                $light
            );
        }

        // A polished clasp plate at the top of the loop.
        imagefilledellipse($canvas, $cx, $cy - $radius, (int) round($bead * 3.4), (int) round($bead * 2.2), $plate);
        imageellipse($canvas, $cx, $cy - $radius, (int) round($bead * 3.4), (int) round($bead * 2.2), $dark);
    }

    private function drawNecklace($canvas, int $variant, int $metal, int $light, int $dark, int $plate): void
    {
        $cx = 600;
        $span = $variant === 1 ? 340 : 400;
        $topY = $variant === 1 ? 250 : 210;
        $bottomY = $variant === 1 ? 850 : 900;
        $bead = $variant === 1 ? 22 : 26;
        $steps = 15;

        $points = [];

        for ($i = 0; $i <= $steps; $i++) {
            $ratio = $i / $steps;
            $points[] = [$cx - $span + ($span * $ratio), $topY + (($bottomY - $topY) * $ratio)];
            $points[] = [$cx + $span - ($span * $ratio), $topY + (($bottomY - $topY) * $ratio)];
        }

        foreach ($points as [$x, $y]) {
            $x = (int) round($x);
            $y = (int) round($y);
            imagefilledellipse($canvas, $x + 3, $y + 4, $bead * 2, $bead * 2, $dark);
            imagefilledellipse($canvas, $x, $y, $bead * 2, $bead * 2, $metal);
            imagefilledellipse($canvas, $x - 5, $y - 6, (int) round($bead * 0.9), (int) round($bead * 0.9), $light);
        }

        // The pendant/plaque at the bottom of the chain.
        imagefilledellipse($canvas, $cx + 4, $bottomY + 8, 210, 210, $dark);
        imagefilledellipse($canvas, $cx, $bottomY, 200, 200, $plate);
        imageellipse($canvas, $cx, $bottomY, 150, 150, $metal);
        imagefilledellipse($canvas, $cx - 30, $bottomY - 34, 54, 54, $light);
    }
}
