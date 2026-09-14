<?php

namespace Database\Seeders;

use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // === Users ===
        // role and email_verified_at are $guarded: firstOrCreate silently drops them,
        // so they are force-filled explicitly after creation.
        $admin = User::firstOrCreate(
            ['phone' => '09000000000'],
            [
                'name' => 'Admin Dev',
                'email' => 'admin@dev.local',
                'password' => 'password',
            ]
        );
        $admin->forceFill(['role' => 'admin', 'email_verified_at' => now()])->save();

        $customer = User::firstOrCreate(
            ['phone' => '09000000001'],
            [
                'name' => 'Customer Dev',
                'email' => 'customer@dev.local',
                'password' => 'password',
            ]
        );
        $customer->forceFill(['role' => 'customer', 'email_verified_at' => now()])->save();

        // === Colors ===
        $black = Color::firstOrCreate(['name' => 'مشکی مات'], ['code_hex' => '#1a1a1a', 'is_active' => true, 'sort_order' => 1]);
        $silver = Color::firstOrCreate(['name' => 'نقره‌ای'], ['code_hex' => '#C0C0C0', 'is_active' => true, 'sort_order' => 2]);
        $gold = Color::firstOrCreate(['name' => 'طلایی'], ['code_hex' => '#FFD700', 'is_active' => true, 'sort_order' => 3]);
        $white = Color::firstOrCreate(['name' => 'سفید'], ['code_hex' => '#FFFFFF', 'is_active' => true, 'sort_order' => 4]);
        $navy = Color::firstOrCreate(['name' => 'سرمه‌ای'], ['code_hex' => '#000080', 'is_active' => true, 'sort_order' => 5]);
        $red = Color::firstOrCreate(['name' => 'قرمز'], ['code_hex' => '#DC143C', 'is_active' => true, 'sort_order' => 6]);
        $blue = Color::firstOrCreate(['name' => 'آبی تیره'], ['code_hex' => '#1E3A5F', 'is_active' => true, 'sort_order' => 7]);
        $copper = Color::firstOrCreate(['name' => 'مسی'], ['code_hex' => '#B87333', 'is_active' => true, 'sort_order' => 8]);
        $green = Color::firstOrCreate(['name' => 'سبز تیره'], ['code_hex' => '#006400', 'is_active' => true, 'sort_order' => 9]);
        $purple = Color::firstOrCreate(['name' => 'بنفش'], ['code_hex' => '#4B0082', 'is_active' => true, 'sort_order' => 10]);

        // === Products ===
        $bankCard = Product::firstOrCreate(
            ['slug' => 'bank-card'],
            [
                'type' => 'bank',
                'customization_workflow' => 'bank_card',
                'name' => 'کارت بانکی',
                'description' => 'کارت بانکی با طرح سفارشی',
                'base_price' => 850000,
                'supports_chip_selection' => true,
                'is_active' => true,
            ]
        );

        $fuelCard = Product::firstOrCreate(
            ['slug' => 'fuel-card'],
            [
                'type' => 'fuel',
                'customization_workflow' => 'fuel_card',
                'name' => 'کارت سوخت',
                'description' => 'کارت سوخت با طرح سفارشی',
                'base_price' => 450000,
                'supports_chip_selection' => false,
                'is_active' => true,
            ]
        );

        // === Product Color Prices ===
        $bankPrices = [
            [$black->id, 850000],
            [$silver->id, 750000],
            [$gold->id, 950000],
            [$white->id, 700000],
            [$navy->id, 800000],
        ];

        foreach ($bankPrices as [$colorId, $price]) {
            ProductColorPrice::firstOrCreate(
                ['product_id' => $bankCard->id, 'color_id' => $colorId],
                ['price' => $price, 'is_active' => true]
            );
        }

        ProductColorPrice::firstOrCreate(
            ['product_id' => $fuelCard->id, 'color_id' => $black->id],
            ['price' => 450000, 'is_active' => true]
        );

        // === Design Categories ===
        $catCrypto = CateDesign::firstOrCreate(['name' => 'رمزارزها'], ['slug' => 'crypto', 'is_active' => true, 'sort_order' => 1]);
        $catNature = CateDesign::firstOrCreate(['name' => 'طبیعت'], ['slug' => 'nature', 'is_active' => true, 'sort_order' => 2]);
        $catAbstract = CateDesign::firstOrCreate(['name' => 'ابستراکت'], ['slug' => 'abstract', 'is_active' => true, 'sort_order' => 3]);
        $catCar = CateDesign::firstOrCreate(['name' => 'ماشین‌ها'], ['slug' => 'cars', 'is_active' => true, 'sort_order' => 4]);

        // === Designs (using cate_design_id) ===
        $designBtcClassic = Design::firstOrCreate(['name' => 'بیتکوین کلاسیک'], ['cate_design_id' => $catCrypto->id, 'slug' => 'btc-classic', 'is_active' => true]);
        $designBtcGold = Design::firstOrCreate(['name' => 'بیتکوین طلایی'], ['cate_design_id' => $catCrypto->id, 'slug' => 'btc-gold', 'is_active' => true]);
        $designEthDiamond = Design::firstOrCreate(['name' => 'اتریوم الماسی'], ['cate_design_id' => $catCrypto->id, 'slug' => 'eth-diamond', 'is_active' => true]);
        $designEthNeon = Design::firstOrCreate(['name' => 'اتریوم نئونی'], ['cate_design_id' => $catCrypto->id, 'slug' => 'eth-neon', 'is_active' => true]);
        $designDoge = Design::firstOrCreate(['name' => 'دوج کوین'], ['cate_design_id' => $catCrypto->id, 'slug' => 'doge', 'is_active' => true]);
        $designMountain = Design::firstOrCreate(['name' => 'قله برفی'], ['cate_design_id' => $catNature->id, 'slug' => 'mountain', 'is_active' => true]);
        $designOcean = Design::firstOrCreate(['name' => 'امواج'], ['cate_design_id' => $catNature->id, 'slug' => 'ocean', 'is_active' => true]);
        $designGrid = Design::firstOrCreate(['name' => 'شبکه‌ای'], ['cate_design_id' => $catAbstract->id, 'slug' => 'grid', 'is_active' => true]);
        $designNeon = Design::firstOrCreate(['name' => 'نئون سبز'], ['cate_design_id' => $catAbstract->id, 'slug' => 'neon-green', 'is_active' => true]);
        $designMustang = Design::firstOrCreate(['name' => 'فورد موستانگ'], ['cate_design_id' => $catCar->id, 'slug' => 'mustang', 'is_active' => true]);
        $designLambo = Design::firstOrCreate(['name' => 'لامبورگینی'], ['cate_design_id' => $catCar->id, 'slug' => 'lamborghini', 'is_active' => true]);
        $designPorsche = Design::firstOrCreate(['name' => 'پورشه ۹۱۱'], ['cate_design_id' => $catCar->id, 'slug' => 'porsche', 'is_active' => true]);

        // === Design Images ===
        $di1 = DesignImage::firstOrCreate(
            ['design_id' => $designBtcClassic->id, 'color_id' => $gold->id],
            ['image_path' => 'design-images/btc-classic-gold.png', 'is_active' => true]
        );
        $di2 = DesignImage::firstOrCreate(
            ['design_id' => $designBtcClassic->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/btc-classic-black.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designBtcClassic->id, 'color_id' => $navy->id],
            ['image_path' => 'design-images/btc-classic-navy.png', 'is_active' => true]
        );

        $di4 = DesignImage::firstOrCreate(
            ['design_id' => $designBtcGold->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/btc-gold-black.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designBtcGold->id, 'color_id' => $silver->id],
            ['image_path' => 'design-images/btc-gold-silver.png', 'is_active' => true]
        );

        DesignImage::firstOrCreate(
            ['design_id' => $designEthDiamond->id, 'color_id' => $silver->id],
            ['image_path' => 'design-images/eth-diamond-silver.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designEthDiamond->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/eth-diamond-black.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designEthDiamond->id, 'color_id' => $white->id],
            ['image_path' => 'design-images/eth-diamond-white.png', 'is_active' => true]
        );

        $di9 = DesignImage::firstOrCreate(
            ['design_id' => $designEthNeon->id, 'color_id' => $blue->id],
            ['image_path' => 'design-images/eth-neon-blue.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designEthNeon->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/eth-neon-black.png', 'is_active' => true]
        );

        DesignImage::firstOrCreate(
            ['design_id' => $designDoge->id, 'color_id' => $gold->id],
            ['image_path' => 'design-images/doge-gold.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designDoge->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/doge-black.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designDoge->id, 'color_id' => $red->id],
            ['image_path' => 'design-images/doge-red.png', 'is_active' => true]
        );

        DesignImage::firstOrCreate(
            ['design_id' => $designMountain->id, 'color_id' => $white->id],
            ['image_path' => 'design-images/mountain-white.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designMountain->id, 'color_id' => $navy->id],
            ['image_path' => 'design-images/mountain-navy.png', 'is_active' => true]
        );

        DesignImage::firstOrCreate(
            ['design_id' => $designOcean->id, 'color_id' => $blue->id],
            ['image_path' => 'design-images/ocean-blue.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designOcean->id, 'color_id' => $silver->id],
            ['image_path' => 'design-images/ocean-silver.png', 'is_active' => true]
        );

        DesignImage::firstOrCreate(
            ['design_id' => $designGrid->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/grid-black.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designGrid->id, 'color_id' => $copper->id],
            ['image_path' => 'design-images/grid-copper.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designGrid->id, 'color_id' => $silver->id],
            ['image_path' => 'design-images/grid-silver.png', 'is_active' => true]
        );

        $di19 = DesignImage::firstOrCreate(
            ['design_id' => $designNeon->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/neon-black.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designNeon->id, 'color_id' => $green->id],
            ['image_path' => 'design-images/neon-green.png', 'is_active' => true]
        );

        DesignImage::firstOrCreate(
            ['design_id' => $designMustang->id, 'color_id' => $red->id],
            ['image_path' => 'design-images/mustang-red.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designMustang->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/mustang-black.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designMustang->id, 'color_id' => $silver->id],
            ['image_path' => 'design-images/mustang-silver.png', 'is_active' => true]
        );

        DesignImage::firstOrCreate(
            ['design_id' => $designLambo->id, 'color_id' => $gold->id],
            ['image_path' => 'design-images/lambo-yellow.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designLambo->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/lambo-black.png', 'is_active' => true]
        );

        DesignImage::firstOrCreate(
            ['design_id' => $designPorsche->id, 'color_id' => $red->id],
            ['image_path' => 'design-images/porsche-red.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designPorsche->id, 'color_id' => $white->id],
            ['image_path' => 'design-images/porsche-white.png', 'is_active' => true]
        );
        DesignImage::firstOrCreate(
            ['design_id' => $designPorsche->id, 'color_id' => $black->id],
            ['image_path' => 'design-images/porsche-black.png', 'is_active' => true]
        );

        // === Design Color Compatibilities (is_allowed=true for most, false for a few) ===
        // Default: all combinations are allowed (created on demand).
        // Seed a few forbidden combinations for testing.
        DesignColorCompatibility::firstOrCreate(
            ['design_image_id' => $di4->id, 'card_color_id' => $black->id],
            ['is_allowed' => false]
        );
        DesignColorCompatibility::firstOrCreate(
            ['design_image_id' => $di9->id, 'card_color_id' => $white->id],
            ['is_allowed' => false]
        );
        DesignColorCompatibility::firstOrCreate(
            ['design_image_id' => $di19->id, 'card_color_id' => $white->id],
            ['is_allowed' => false]
        );
        DesignColorCompatibility::firstOrCreate(
            ['design_image_id' => $di1->id, 'card_color_id' => $gold->id],
            ['is_allowed' => false]
        );

        $this->call([
            ManualPaymentSettingSeeder::class,
            CmsContentSeeder::class,
        ]);

        echo "\nSeed completed!\n";
        echo "Users: admin (09000000000/password), customer (09000000001/password)\n";
        echo "Colors: 10\n";
        echo "Products: 2 (bank-card, fuel-card)\n";
        echo "Design categories: 4\n";
        echo "Designs: 12\n";
        echo "Design images: ~28\n";
        echo "Forbidden compatibilities: 4\n";
    }
}
