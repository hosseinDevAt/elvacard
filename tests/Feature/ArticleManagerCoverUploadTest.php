<?php

namespace Tests\Feature;

use App\Livewire\Admin\ArticleManager;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleManagerCoverUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function saveNewArticle(array $props = []): Article
    {
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'مقاله بدون تصویر')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasNoErrors();

        return Article::query()->latest('id')->firstOrFail();
    }

    public function test_article_cover_image_is_uploaded_and_stored(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'مقاله با تصویر')
            ->set('content', 'محتوا')
            ->set('coverImageUpload', UploadedFile::fake()->image('cover.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::where('title', 'مقاله با تصویر')->firstOrFail();

        $this->assertNotNull($article->cover_image);
        $this->assertStringStartsWith('articles/', $article->cover_image);
        Storage::disk('public')->assertExists($article->cover_image);
    }

    public function test_client_provided_cover_path_is_never_trusted(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'مقاله بدون تصویر')
            ->set('content', 'محتوا')
            ->set('coverImage', '../../../payload.svg')
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::where('title', 'مقاله بدون تصویر')->firstOrFail();
        $this->assertNull($article->cover_image);
    }

    public function test_replacing_cover_image_uploads_new_file_and_removes_old_when_unreferenced(): void
    {
        Storage::fake('public');
        $this->saveNewArticle();
        $article = Article::query()->latest('id')->firstOrFail();

        $component = Livewire::actingAs($this->admin())->test(ArticleManager::class);
        $component->set('editingId', $article->id)
            ->set('title', 'مقاله بدون تصویر')
            ->set('content', 'محتوا')
            ->set('coverImageUpload', UploadedFile::fake()->image('first.png'))
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();
        $oldPath = $article->cover_image;
        $this->assertNotNull($oldPath);
        Storage::disk('public')->assertExists($oldPath);

        $component->set('editingId', $article->id)
            ->set('title', 'مقاله بدون تصویر')
            ->set('content', 'محتوا')
            ->set('coverImageUpload', UploadedFile::fake()->image('second.png'))
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();

        $this->assertNotSame($oldPath, $article->cover_image);
        Storage::disk('public')->assertExists($article->cover_image);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_removing_cover_image_clears_it_and_deletes_file(): void
    {
        Storage::fake('public');
        $this->saveNewArticle();
        $article = Article::query()->latest('id')->firstOrFail();

        $component = Livewire::actingAs($this->admin())->test(ArticleManager::class);
        $component->set('editingId', $article->id)
            ->set('title', 'مقاله بدون تصویر')
            ->set('content', 'محتوا')
            ->set('coverImageUpload', UploadedFile::fake()->image('cover.png'))
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();
        $oldPath = $article->cover_image;

        $component->set('editingId', $article->id)
            ->set('title', 'مقاله بدون تصویر')
            ->set('content', 'محتوا')
            ->set('removeCoverImage', true)
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();

        $this->assertNull($article->cover_image);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_malformed_svg_cover_is_rejected_and_not_stored(): void
    {
        Storage::fake('public');

        $svg = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            .'<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'مقاله سگ')
            ->set('content', 'محتوا')
            ->set('coverImageUpload', UploadedFile::fake()->createWithContent('cover.svg', $svg))
            ->call('save')
            ->assertHasErrors('coverImageUpload');

        Storage::disk('public')->assertDirectoryEmpty('articles');
        $this->assertSame(0, Article::count());
    }
}
