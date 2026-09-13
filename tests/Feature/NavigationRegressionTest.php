<?php

namespace Tests\Feature;

use App\Enums\MenuItemTypeEnum;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_menu_external_links_are_not_spa_navigated(): void
    {
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);
        MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => MenuItemTypeEnum::URL->value,
            'title' => 'اینستاگرام',
            'custom_url' => 'https://social.example/elvacard',
            'target' => '_blank',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();

        // External/new-tab links must stay plain anchors: no wire:navigate, otherwise Livewire
        // intercepts the click and tries an in-tab fetch of the external URL, aborting the action
        // ("opens page then returns back to previous page with refresh").
        $response->assertSee('href="https://social.example/elvacard" target="_blank" rel="noopener noreferrer"', false);
        $response->assertDontSee('href="https://social.example/elvacard" wire:navigate', false);
    }

    public function test_header_menu_internal_links_keep_spa_navigation(): void
    {
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);
        MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => MenuItemTypeEnum::URL->value,
            'title' => 'فروشگاه',
            'custom_url' => route('catalog.products.index'),
            'target' => '_self',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('href="'.route('catalog.products.index').'" wire:navigate', false);
    }
}
