<?php

namespace Database\Seeders;

use App\Models\CardType;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorRestriction;
use App\Models\DesignImage;
use App\Models\GroupDesign;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['phone' => '09000000000'],
            ['name' => 'ادمین', 'password' => bcrypt('123456')]
        );

        // === رنگ‌ها ===
        $black = Color::create(['name' => 'مشکی مات', 'color_code' => '#1a1a1a']);
        $silver = Color::create(['name' => 'نقره‌ای', 'color_code' => '#C0C0C0']);
        $gold = Color::create(['name' => 'طلایی', 'color_code' => '#FFD700']);
        $white = Color::create(['name' => 'سفید', 'color_code' => '#FFFFFF']);
        $navy = Color::create(['name' => 'سرمه‌ای', 'color_code' => '#000080']);
        $red = Color::create(['name' => 'قرمز', 'color_code' => '#DC143C']);
        $blue = Color::create(['name' => 'آبی تیره', 'color_code' => '#1E3A5F']);
        $copper = Color::create(['name' => 'مسی', 'color_code' => '#B87333']);
        $green = Color::create(['name' => 'سبز تیره', 'color_code' => '#006400']);
        $purple = Color::create(['name' => 'بنفش', 'color_code' => '#4B0082']);

        // === انواع کارت (۵ بانکی + ۱ سوخت-مشکی) ===
        CardType::insert([
            ['type' => 'bank', 'color_id' => $black->id, 'base_price' => 850000, 'is_available' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'bank', 'color_id' => $silver->id, 'base_price' => 750000, 'is_available' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'bank', 'color_id' => $gold->id, 'base_price' => 950000, 'is_available' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'bank', 'color_id' => $white->id, 'base_price' => 700000, 'is_available' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'bank', 'color_id' => $navy->id, 'base_price' => 800000, 'is_available' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'fuel', 'color_id' => $black->id, 'base_price' => 450000, 'is_available' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // === دسته‌بندی طرح‌ها ===
        $catCrypto = CateDesign::create(['name' => 'رمزارزها', 'is_active' => true]);
        $catNature = CateDesign::create(['name' => 'طبیعت', 'is_active' => true]);
        $catAbstract = CateDesign::create(['name' => 'ابستراکت', 'is_active' => true]);
        $catCar = CateDesign::create(['name' => 'ماشین‌ها', 'is_active' => true]);

        // === گروه طرح‌ها ===
        $grpBitcoin = GroupDesign::create(['cate_design_id' => $catCrypto->id, 'name' => 'بیتکوین']);
        $grpEthereum = GroupDesign::create(['cate_design_id' => $catCrypto->id, 'name' => 'اتریوم']);
        $grpDoge = GroupDesign::create(['cate_design_id' => $catCrypto->id, 'name' => 'دوج‌کوین']);
        $grpMountain = GroupDesign::create(['cate_design_id' => $catNature->id, 'name' => 'کوهستان']);
        $grpOcean = GroupDesign::create(['cate_design_id' => $catNature->id, 'name' => 'اقیانوس']);
        $grpGeo = GroupDesign::create(['cate_design_id' => $catAbstract->id, 'name' => 'خطوط هندسی']);
        $grpNeon = GroupDesign::create(['cate_design_id' => $catAbstract->id, 'name' => 'نئونی']);
        $grpSport = GroupDesign::create(['cate_design_id' => $catCar->id, 'name' => 'اسپرت']);
        $grpHeavy = GroupDesign::create(['cate_design_id' => $catCar->id, 'name' => 'ماشین سنگین']);

        // === طرح‌ها ===
        $designBtcClassic = Design::create(['group_design_id' => $grpBitcoin->id, 'name' => 'بیتکوین کلاسیک', 'image_path' => 'designs/bitcoin-classic.png']);
        $designBtcGold = Design::create(['group_design_id' => $grpBitcoin->id, 'name' => 'بیتکوین طلایی', 'image_path' => 'designs/bitcoin-gold.png']);
        $designEthDiamond = Design::create(['group_design_id' => $grpEthereum->id, 'name' => 'اتریوم الماسی', 'image_path' => 'designs/ethereum-diamond.png']);
        $designEthNeon = Design::create(['group_design_id' => $grpEthereum->id, 'name' => 'اتریوم نئونی', 'image_path' => 'designs/ethereum-neon.png']);
        $designDoge = Design::create(['group_design_id' => $grpDoge->id, 'name' => 'دوج کوین', 'image_path' => 'designs/dogecoin.png']);
        $designMountain = Design::create(['group_design_id' => $grpMountain->id, 'name' => 'قله برفی', 'image_path' => 'designs/mountain-snow.png']);
        $designOcean = Design::create(['group_design_id' => $grpOcean->id, 'name' => 'امواج', 'image_path' => 'designs/ocean-waves.png']);
        $designGrid = Design::create(['group_design_id' => $grpGeo->id, 'name' => 'شبکه‌ای', 'image_path' => 'designs/geometric-grid.png']);
        $designNeon = Design::create(['group_design_id' => $grpNeon->id, 'name' => 'نئون سبز', 'image_path' => 'designs/neon-green.png']);
        $designMustang = Design::create(['group_design_id' => $grpSport->id, 'name' => 'فورد موستانگ', 'image_path' => 'designs/mustang.png']);
        $designLambo = Design::create(['group_design_id' => $grpSport->id, 'name' => 'لامبورگینی', 'image_path' => 'designs/lamborghini.png']);
        $designPorsche = Design::create(['group_design_id' => $grpSport->id, 'name' => 'پورشه ۹۱۱', 'image_path' => 'designs/porsche.png']);

        // ماشین سنگین
        $designTruck = Design::create(['group_design_id' => $grpHeavy->id, 'name' => 'کامیون', 'image_path' => 'designs/truck.png']);
        $designBus = Design::create(['group_design_id' => $grpHeavy->id, 'name' => 'اتوبوس', 'image_path' => 'designs/bus.png']);
        $designCrane = Design::create(['group_design_id' => $grpHeavy->id, 'name' => 'جرثقیل', 'image_path' => 'designs/crane.png']);

        // === تصاویر طرح (هر طرح ۲-۳ رنگ) ===
        // بیتکوین کلاسیک
        $di1 = DesignImage::create(['design_id' => $designBtcClassic->id, 'color_id' => $gold->id, 'image_path' => 'design-images/btc-classic-gold.png']);
        $di2 = DesignImage::create(['design_id' => $designBtcClassic->id, 'color_id' => $black->id, 'image_path' => 'design-images/btc-classic-black.png']);
        DesignImage::create(['design_id' => $designBtcClassic->id, 'color_id' => $navy->id, 'image_path' => 'design-images/btc-classic-navy.png']);

        // بیتکوین طلایی
        $di4 = DesignImage::create(['design_id' => $designBtcGold->id, 'color_id' => $black->id, 'image_path' => 'design-images/btc-gold-black.png']);
        $di5 = DesignImage::create(['design_id' => $designBtcGold->id, 'color_id' => $silver->id, 'image_path' => 'design-images/btc-gold-silver.png']);

        // اتریوم الماسی
        DesignImage::create(['design_id' => $designEthDiamond->id, 'color_id' => $silver->id, 'image_path' => 'design-images/eth-diamond-silver.png']);
        DesignImage::create(['design_id' => $designEthDiamond->id, 'color_id' => $black->id, 'image_path' => 'design-images/eth-diamond-black.png']);
        DesignImage::create(['design_id' => $designEthDiamond->id, 'color_id' => $white->id, 'image_path' => 'design-images/eth-diamond-white.png']);

        // اتریوم نئونی
        $di9 = DesignImage::create(['design_id' => $designEthNeon->id, 'color_id' => $blue->id, 'image_path' => 'design-images/eth-neon-blue.png']);
        $di10 = DesignImage::create(['design_id' => $designEthNeon->id, 'color_id' => $black->id, 'image_path' => 'design-images/eth-neon-black.png']);

        // دوج
        DesignImage::create(['design_id' => $designDoge->id, 'color_id' => $gold->id, 'image_path' => 'design-images/doge-gold.png']);
        DesignImage::create(['design_id' => $designDoge->id, 'color_id' => $black->id, 'image_path' => 'design-images/doge-black.png']);
        DesignImage::create(['design_id' => $designDoge->id, 'color_id' => $red->id, 'image_path' => 'design-images/doge-red.png']);

        // کوهستان
        DesignImage::create(['design_id' => $designMountain->id, 'color_id' => $white->id, 'image_path' => 'design-images/mountain-white.png']);
        DesignImage::create(['design_id' => $designMountain->id, 'color_id' => $navy->id, 'image_path' => 'design-images/mountain-navy.png']);

        // اقیانوس
        DesignImage::create(['design_id' => $designOcean->id, 'color_id' => $blue->id, 'image_path' => 'design-images/ocean-blue.png']);
        DesignImage::create(['design_id' => $designOcean->id, 'color_id' => $silver->id, 'image_path' => 'design-images/ocean-silver.png']);

        // شبکه‌ای
        DesignImage::create(['design_id' => $designGrid->id, 'color_id' => $black->id, 'image_path' => 'design-images/grid-black.png']);
        DesignImage::create(['design_id' => $designGrid->id, 'color_id' => $copper->id, 'image_path' => 'design-images/grid-copper.png']);
        DesignImage::create(['design_id' => $designGrid->id, 'color_id' => $silver->id, 'image_path' => 'design-images/grid-silver.png']);

        // نئون سبز
        $di19 = DesignImage::create(['design_id' => $designNeon->id, 'color_id' => $black->id, 'image_path' => 'design-images/neon-black.png']);
        DesignImage::create(['design_id' => $designNeon->id, 'color_id' => $green->id, 'image_path' => 'design-images/neon-green.png']);

        // موستانگ
        DesignImage::create(['design_id' => $designMustang->id, 'color_id' => $red->id, 'image_path' => 'design-images/mustang-red.png']);
        DesignImage::create(['design_id' => $designMustang->id, 'color_id' => $black->id, 'image_path' => 'design-images/mustang-black.png']);
        DesignImage::create(['design_id' => $designMustang->id, 'color_id' => $silver->id, 'image_path' => 'design-images/mustang-silver.png']);

        // لامبورگینی
        DesignImage::create(['design_id' => $designLambo->id, 'color_id' => $gold->id, 'image_path' => 'design-images/lambo-yellow.png']);
        DesignImage::create(['design_id' => $designLambo->id, 'color_id' => $black->id, 'image_path' => 'design-images/lambo-black.png']);

        // پورشه
        DesignImage::create(['design_id' => $designPorsche->id, 'color_id' => $red->id, 'image_path' => 'design-images/porsche-red.png']);
        DesignImage::create(['design_id' => $designPorsche->id, 'color_id' => $white->id, 'image_path' => 'design-images/porsche-white.png']);
        DesignImage::create(['design_id' => $designPorsche->id, 'color_id' => $black->id, 'image_path' => 'design-images/porsche-black.png']);

        // کامیون
        DesignImage::create(['design_id' => $designTruck->id, 'color_id' => $white->id, 'image_path' => 'design-images/truck-white.png']);
        DesignImage::create(['design_id' => $designTruck->id, 'color_id' => $red->id, 'image_path' => 'design-images/truck-red.png']);

        // اتوبوس
        DesignImage::create(['design_id' => $designBus->id, 'color_id' => $white->id, 'image_path' => 'design-images/bus-white.png']);
        DesignImage::create(['design_id' => $designBus->id, 'color_id' => $blue->id, 'image_path' => 'design-images/bus-blue.png']);

        // جرثقیل
        DesignImage::create(['design_id' => $designCrane->id, 'color_id' => $gold->id, 'image_path' => 'design-images/crane-yellow.png']);
        DesignImage::create(['design_id' => $designCrane->id, 'color_id' => $white->id, 'image_path' => 'design-images/crane-white.png']);

        // === محدودیت‌های رنگی ===
        DesignColorRestriction::insert([
            // بیتکوین طلایی روی مشکی مات اجرا نمی‌شه
            ['design_image_id' => $di4->id, 'forbidden_card_color_id' => $black->id, 'created_at' => now(), 'updated_at' => now()],
            // اتریوم نئونی روی سفید اجرا نمی‌شه
            ['design_image_id' => $di9->id, 'forbidden_card_color_id' => $white->id, 'created_at' => now(), 'updated_at' => now()],
            // نئون سبز روی سفید اجرا نمی‌شه
            ['design_image_id' => $di19->id, 'forbidden_card_color_id' => $white->id, 'created_at' => now(), 'updated_at' => now()],
            // بیتکوین کلاسیک طلایی روی طلایی اجرا نمی‌شه (رنگ روی رنگ خودش)
            ['design_image_id' => $di1->id, 'forbidden_card_color_id' => $gold->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        echo "\n✅ Seed completed!\n";
        echo "🏦 Bank cards: 5 colors (مشکی, نقره‌ای, طلایی, سفید, سرمه‌ای)\n";
        echo "⛽ Fuel cards: 1 color (مشکی مات)\n";
        echo "🎨 Design categories: 4 (رمزارزها, طبیعت, ابستراکت, ماشین‌ها)\n";
        echo "🖼️ Design images: 24\n";
        echo "🚫 Color restrictions: 4\n";
    }
}
