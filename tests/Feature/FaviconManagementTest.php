<?php

namespace Tests\Feature;

use App\Livewire\Admin\AppearanceManager;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FaviconManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * The AppearanceManager save() validates that at least one header menu
     * item exists; seed one minimal item so the rest of the form can persist.
     */
    private function seedHeaderMenuItem(): void
    {
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);

        MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => 'url',
            'route_key' => 'home',
            'title' => 'خانه',
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_appearance_management(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.appearance'))
            ->assertForbidden();
    }

    public function test_admin_can_upload_favicon(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('siteFavicon', UploadedFile::fake()->image('favicon.png', 32, 32))
            ->call('save')
            ->assertHasNoErrors();

        $setting = SiteSetting::where('key', 'site_favicon')->first();

        $this->assertNotNull($setting);
        $this->assertStringStartsWith('favicons/', $setting->value);
        Storage::disk('public')->assertExists($setting->value);
    }

    public function test_invalid_favicon_type_is_rejected(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('siteFavicon', UploadedFile::fake()->create('favicon.txt', 10))
            ->call('save')
            ->assertHasErrors('siteFavicon');
    }

    public function test_replacing_favicon_deletes_previous_file(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        $component = Livewire::actingAs($this->admin())->test(AppearanceManager::class);

        $component->set('siteFavicon', UploadedFile::fake()->image('first.png', 32, 32))
            ->call('save')
            ->assertHasNoErrors();

        $oldPath = SiteSetting::where('key', 'site_favicon')->value('value');

        $component->set('siteFavicon', UploadedFile::fake()->image('second.png', 48, 48))
            ->call('save')
            ->assertHasNoErrors();

        $newPath = SiteSetting::where('key', 'site_favicon')->value('value');

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_admin_can_remove_favicon(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        $component = Livewire::actingAs($this->admin())->test(AppearanceManager::class);

        $component->set('siteFavicon', UploadedFile::fake()->image('favicon.png', 32, 32))
            ->call('save');

        $oldPath = SiteSetting::where('key', 'site_favicon')->value('value');

        $component->call('removeFavicon');

        $this->assertNull(SiteSetting::where('key', 'site_favicon')->first());
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_favicon_link_always_present_with_internal_fallback(): void
    {
        Storage::fake('public');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<link rel="icon"', false)
            ->assertSee('favicon.svg', false);
    }

    public function test_uploaded_svg_is_sanitized_before_storage(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            .'<script>document.body.innerHTML=""</script>'
            .'<foreignObject><iframe src="https://evil.example"></iframe></foreignObject>'
            .'<rect width="10" height="10"/></svg>';

        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('siteFavicon', UploadedFile::fake()->createWithContent('favicon.svg', $svg))
            ->call('save')
            ->assertHasNoErrors();

        $path = SiteSetting::where('key', 'site_favicon')->value('value');

        $this->assertNotNull($path);
        $content = Storage::disk('public')->get($path);

        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('onload', $content);
        $this->assertStringNotContainsString('foreignObject', $content);
        $this->assertStringNotContainsString('iframe', $content);
        $this->assertStringContainsString('<rect', $content);
    }

    public function test_doctype_entity_bomb_svg_is_rejected(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        $svg = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            .'<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';

        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('siteFavicon', UploadedFile::fake()->createWithContent('favicon.svg', $svg))
            ->call('save')
            ->assertHasErrors('siteFavicon');

        Storage::disk('public')->assertDirectoryEmpty('favicons');
    }
}