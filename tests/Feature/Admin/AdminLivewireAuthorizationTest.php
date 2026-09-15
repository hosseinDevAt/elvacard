<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\ColorManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Livewire\Admin\FaqItemManager;
use App\Livewire\Admin\HomepageSectionManager;
use App\Livewire\Admin\MenuItemManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\OrderManager;
use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Admin\SiteSettingManager;
use App\Livewire\Admin\UserManager;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every admin component; a guest must be rejected on the initial mount.
     */
    private function allAdminComponents(): array
    {
        return [
            ArticleManager::class,
            ColorManager::class,
            Dashboard::class,
            DesignImageManager::class,
            DesignManager::class,
            DesignWizard::class,
            FaqItemManager::class,
            HomepageSectionManager::class,
            MenuItemManager::class,
            MenuManager::class,
            OrderManager::class,
            PageManager::class,
            ProductColorPriceManager::class,
            ProductManager::class,
            SiteSettingManager::class,
            UserManager::class,
        ];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function category(): CateDesign
    {
        return CateDesign::create([
            'name' => 'گارد',
            'slug' => 'guard-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function color(): Color
    {
        return Color::create([
            'name' => 'مشکی',
            'code_hex' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function design(): Design
    {
        return Design::create([
            'cate_design_id' => $this->category()->id,
            'name' => 'طرح اصلی',
            'slug' => 'guard-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function image(): DesignImage
    {
        return DesignImage::create([
            'design_id' => $this->design()->id,
            'color_id' => $this->color()->id,
            'image_path' => 'designs/guard.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_guest_is_rejected_when_mounting_every_admin_component(): void
    {
        foreach ($this->allAdminComponents() as $component) {
            Livewire::test($component)->assertStatus(403);
        }
    }

    public function test_customer_is_rejected_when_mounting_every_admin_component(): void
    {
        foreach ($this->allAdminComponents() as $component) {
            Livewire::actingAs($this->customer())
                ->test($component)
                ->assertStatus(403);
        }
    }

    public function test_admin_can_mount_admin_components(): void
    {
        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->assertOk();

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->assertOk();
    }

    public function test_hydrate_rejects_a_user_whose_role_changed_after_mount(): void
    {
        $image = $this->image();

        $component = Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->assertOk();

        Testable::actingAs($this->customer());

        $component->call('delete', $image->id)->assertStatus(403);

        $this->assertDatabaseHas('design_images', ['id' => $image->id]);
    }
}
