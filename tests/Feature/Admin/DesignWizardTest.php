<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DesignWizard;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DesignWizardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function category(string $name = 'طبیعت'): CateDesign
    {
        return CateDesign::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'is_active' => true]);
    }

    private function color(): Color
    {
        return Color::create(['name' => 'مشکی مات', 'code_hex' => '#1a1a1a', 'is_active' => true, 'sort_order' => 1]);
    }

    public function test_admin_can_open_create_wizard(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.designs.create'))
            ->assertStatus(200);
    }

    public function test_customer_is_denied_wizard(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.designs.create'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_wizard(): void
    {
        $this->get(route('admin.designs.create'))
            ->assertRedirect(route('login'));
    }

    public function test_edit_wizard_requires_existing_design(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.designs.edit', 999))
            ->assertNotFound();
    }

    public function test_create_workflow_persists_every_step(): void
    {
        $category = $this->category();
        $color = $this->color();

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'بیتکوین کلاسیک')
            ->set('description', 'طرح کلاسیک بیتکوین')
            ->set('sortOrder', 3)
            ->call('next');

        // Step 1 → 2: design persisted with a slug.
        $component->assertSet('step', 2)
            ->assertSet('designId', fn ($id) => is_int($id));

        $design = Design::query()->where('name', 'بیتکوین کلاسیک')->firstOrFail();
        $this->assertNotEmpty($design->slug);
        $this->assertSame(3, (int) $design->sort_order);

        // Step 2: add an image.
        $component->set('colorId', $color->id)
            ->set('imagePath', 'design-images/btc-classic-black.png')
            ->call('saveImage');

        $image = DesignImage::query()->where('design_id', $design->id)->firstOrFail();
        $this->assertSame((int) $color->id, (int) $image->color_id);

        // Steps 2 → 3 → 4: compatibility toggle creates the row.
        $component->call('next')
            ->assertSet('step', 3)
            ->call('next')
            ->assertSet('step', 4)
            ->call('toggleCompatibility', $image->id, $color->id);

        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        // Step 4 → 5: review then save redirects to the list.
        $component->call('next')
            ->assertSet('step', 5)
            ->call('save')
            ->assertRedirect(route('admin.designs'))
            ->assertSessionHas('success');
    }

    public function test_step_one_requires_category_and_name(): void
    {
        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->call('next')
            ->assertHasErrors(['cateDesignId' => 'required', 'name' => 'required']);

        $this->assertDatabaseCount('designs', 0);
    }

    public function test_category_can_be_created_inside_wizard(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('newCategoryName', 'رمز ارزها')
            ->call('addCategory')
            ->assertHasNoErrors()
            ->assertSet('newCategoryName', '');

        $category = CateDesign::query()->where('name', 'رمز ارزها')->first();
        $this->assertNotNull($category);

        $component->assertSet('cateDesignId', (int) $category->id);
    }

    public function test_edit_workflow_loads_existing_design(): void
    {
        $category = $this->category();
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح موجود',
            'slug' => 'existing-design',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->assertSet('step', 1)
            ->assertSet('name', 'طرح موجود')
            ->assertSet('cateDesignId', (int) $category->id)
            ->call('next')
            ->assertSet('step', 2);
    }

    public function test_edit_workflow_updates_basic_info(): void
    {
        $category = $this->category();
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح اولیه',
            'slug' => 'initial-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('name', 'نام به‌روزشده')
            ->set('description', 'توضیح جدید')
            ->call('next')
            ->assertSet('step', 2);

        $this->assertDatabaseHas('designs', [
            'id' => $design->id,
            'name' => 'نام به‌روزشده',
            'description' => 'توضیح جدید',
        ]);
    }

    public function test_edit_page_renders_existing_images(): void
    {
        $category = $this->category();
        $color = $this->color();
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح با تصویر',
            'slug' => 'design-with-image',
            'is_active' => true,
        ]);
        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'design-images/sample-black.png',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('next')
            ->assertSet('step', 2)
            ->assertSee($image->image_path);
    }

    public function test_image_operations_are_scoped_to_current_design(): void
    {
        $category = $this->category();
        $color = $this->color();

        $designA = Design::create(['cate_design_id' => $category->id, 'name' => 'طرح A', 'slug' => 'design-a', 'is_active' => true]);
        $designB = Design::create(['cate_design_id' => $category->id, 'name' => 'طرح B', 'slug' => 'design-b', 'is_active' => true]);

        $imageA = DesignImage::create(['design_id' => $designA->id, 'color_id' => $color->id, 'image_path' => 'a.png', 'is_active' => true]);

        $wizard = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $designB->id]);

        // Editing an image that belongs to another design must be rejected
        // (guard leaves the editing state untouched).
        $wizard->call('editImage', $imageA->id)
            ->assertSet('editingImageId', null);

        // Toggling compatibility for another design's image must be rejected.
        $wizard->call('toggleCompatibility', $imageA->id, $color->id);

        $this->assertDatabaseCount('design_color_compatibilities', 0);
        $this->assertDatabaseHas('design_images', ['id' => $imageA->id, 'image_path' => 'a.png']);
    }
}