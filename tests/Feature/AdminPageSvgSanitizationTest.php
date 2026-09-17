<?php

namespace Tests\Feature;

use App\Livewire\Admin\PageManager;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPageSvgSanitizationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_page_svg_image_is_sanitized_before_storage(): void
    {
        Storage::fake('public');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            .'<script>document.body.innerHTML=""</script>'
            .'<foreignObject><iframe src="https://evil.example"></iframe></foreignObject>'
            .'<rect width="10" height="10"/></svg>';

        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->set('pageType', 'general')
            ->set('title', 'صفحه با تصویر')
            ->set('content', 'محتوا')
            ->set('imageUpload', UploadedFile::fake()->createWithContent('page.svg', $svg))
            ->call('save')
            ->assertHasNoErrors();

        $page = Page::where('title', 'صفحه با تصویر')->firstOrFail();

        $this->assertStringStartsWith('pages/', $page->image_path);
        $content = Storage::disk('public')->get($page->image_path);

        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('onload', $content);
        $this->assertStringNotContainsString('foreignObject', $content);
        $this->assertStringNotContainsString('iframe', $content);
        $this->assertStringContainsString('<rect', $content);
    }

    public function test_malformed_page_svg_is_rejected_and_not_stored(): void
    {
        Storage::fake('public');

        $svg = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            .'<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';

        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->set('pageType', 'general')
            ->set('title', 'صفحه مخرب')
            ->set('content', 'محتوا')
            ->set('imageUpload', UploadedFile::fake()->createWithContent('page.svg', $svg))
            ->call('save')
            ->assertHasErrors('imageUpload');

        Storage::disk('public')->assertDirectoryEmpty('pages');
        $this->assertSame(0, Page::count());
    }

    public function test_regular_page_image_is_stored_as_is(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->set('pageType', 'general')
            ->set('title', 'صفحه عادی')
            ->set('content', 'محتوا')
            ->set('imageUpload', UploadedFile::fake()->image('page.png'))
            ->call('save')
            ->assertHasNoErrors();

        $page = Page::where('title', 'صفحه عادی')->firstOrFail();

        $this->assertStringStartsWith('pages/', $page->image_path);
        Storage::disk('public')->assertExists($page->image_path);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('صفحه عادی');
    }
}
