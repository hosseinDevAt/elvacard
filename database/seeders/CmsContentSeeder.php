<?php

namespace Database\Seeders;

use App\Models\HomepageSection;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class CmsContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPages();
        $this->seedHeaderMenu();
        $this->seedFooterMenu();
        $this->seedSiteSettings();
        $this->seedHomepageSections();
    }

    private function seedPages(): void
    {
        Page::firstOrCreate(
            ['slug' => 'about-us'],
            [
                'page_type' => 'about',
                'title' => 'درباره ما',
                'content' => 'ما تیمی از متخصصان با تجربه در زمینه طراحی و تولید کارت‌های شخصی فلزی هستیم. با استفاده از جدیدترین تکنولوژی‌های حکاکی و برش لیزری، کارت‌هایی با کیفیت بالا و طراحی منحصربه‌فرد تولید می‌کنیم.',
                'meta_title' => 'درباره ما - الواکارت',
                'meta_description' => 'آشنایی با تیم الواکارت و خدمات ما',
                'is_active' => true,
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'contact-us'],
            [
                'page_type' => 'contact',
                'title' => 'تماس با ما',
                'content' => 'ما آماده پاسخگویی به سوالات شما هستیم. از طریق راه‌های ارتباطی زیر می‌توانید با ما در تماس باشید.',
                'meta_title' => 'تماس با ما - الواکارت',
                'meta_description' => 'راه‌های ارتباطی با ما',
                'is_active' => true,
            ]
        );
    }

    private function seedHeaderMenu(): void
    {
        $menu = Menu::firstOrCreate(
            ['location' => 'header'],
            ['name' => 'منوی اصلی']
        );

        $items = [
            ['title' => 'خانه', 'route_key' => 'home', 'sort_order' => 1],
            ['title' => 'درباره ما', 'route_key' => 'about', 'sort_order' => 2],
            ['title' => 'تماس با ما', 'route_key' => 'contact', 'sort_order' => 3],
            ['title' => 'فروشگاه', 'route_key' => 'shop', 'sort_order' => 4],
            ['title' => 'طراحی کارت اختصاصی', 'route_key' => 'custom_card_design', 'sort_order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::firstOrCreate(
                ['menu_id' => $menu->id, 'route_key' => $item['route_key']],
                [
                    'item_type' => 'url',
                    'title' => $item['title'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedFooterMenu(): void
    {
        $menu = Menu::firstOrCreate(
            ['location' => 'footer'],
            ['name' => 'منوی فوتر']
        );

        $footerItems = [
            ['title' => 'فروشگاه', 'route_key' => 'shop', 'sort_order' => 1],
            ['title' => 'درباره ما', 'route_key' => 'about', 'sort_order' => 2],
            ['title' => 'تماس با ما', 'route_key' => 'contact', 'sort_order' => 3],
        ];

        foreach ($footerItems as $item) {
            MenuItem::firstOrCreate(
                ['menu_id' => $menu->id, 'route_key' => $item['route_key']],
                [
                    'item_type' => 'url',
                    'title' => $item['title'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedSiteSettings(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'الواکارت', 'type' => 'string', 'group' => 'identity', 'is_public' => true],
            ['key' => 'site_logo', 'value' => null, 'type' => 'string', 'group' => 'identity', 'is_public' => true],
            ['key' => 'site_favicon', 'value' => null, 'type' => 'string', 'group' => 'identity', 'is_public' => true],
            ['key' => 'footer_about_text', 'value' => 'ما متخصصان طراحی و تولید کارت‌های شخصی فلزی با کیفیت بالا هستیم.', 'type' => 'string', 'group' => 'footer', 'is_public' => true],
            ['key' => 'contact_phone', 'value' => null, 'type' => 'string', 'group' => 'contact', 'is_public' => true],
            ['key' => 'contact_email', 'value' => null, 'type' => 'string', 'group' => 'contact', 'is_public' => true],
            ['key' => 'contact_address', 'value' => null, 'type' => 'string', 'group' => 'contact', 'is_public' => true],
        ];

        $banners = [
            ['key' => 'homepage_banner_1', 'value' => json_encode(['image_path' => null, 'title' => 'کارت شخصی فلزی', 'subtitle' => 'طراحی و تولید کارت‌های فلزی منحصربه‌فرد', 'cta_text' => 'مشاهده محصولات', 'cta_url' => '/catalog/products']), 'type' => 'json', 'group' => 'homepage', 'is_public' => true],
            ['key' => 'homepage_banner_2', 'value' => json_encode(['image_path' => null, 'title' => 'طراحی کارت اختصاصی', 'subtitle' => 'طرح دلخواه خود را روی کارت حکاکی کنید', 'cta_text' => 'شروع طراحی', 'cta_url' => '/design']), 'type' => 'json', 'group' => 'homepage', 'is_public' => true],
            ['key' => 'homepage_banner_3', 'value' => json_encode(['image_path' => null, 'title' => 'کیفیت تضمینی', 'subtitle' => 'با بهترین مواد اولیه و تکنولوژی روز', 'cta_text' => 'بیشتر بدانید', 'cta_url' => '/pages/about-us']), 'type' => 'json', 'group' => 'homepage', 'is_public' => true],
        ];

        $settings = array_merge($settings, $banners);

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'is_public' => $setting['is_public'],
                ]
            );
        }

        foreach (['about-us' => 'الواکارت', 'contact-us' => 'الواکارت'] as $slug => $brandName) {
            Page::where('slug', $slug)->update([
                'meta_title' => str_replace('کارت شخصی', $brandName, (string) Page::where('slug', $slug)->value('meta_title')),
            ]);
        }
    }

    private function seedHomepageSections(): void
    {
        HomepageSection::firstOrCreate(
            ['section_type' => 'newest_products'],
            [
                'title' => 'جدیدترین محصولات',
                'content' => null,
                'settings' => ['limit' => 8],
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        HomepageSection::firstOrCreate(
            ['section_type' => 'featured_designs'],
            [
                'title' => 'طرح‌های محبوب',
                'content' => null,
                'settings' => ['limit' => 8],
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        HomepageSection::firstOrCreate(
            ['section_type' => 'articles'],
            [
                'title' => 'آخرین مقالات',
                'content' => null,
                'settings' => ['limit' => 3],
                'sort_order' => 3,
                'is_active' => true,
            ]
        );
    }
}