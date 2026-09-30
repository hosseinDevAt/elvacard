<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\ArticleCategoryManager;
use App\Livewire\Admin\FaqItemManager;
use App\Livewire\Admin\HomepageSectionManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\ProductCategoryManager;
use App\Livewire\Admin\SiteSettingManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaleRecordGuardTest extends TestCase
{
    use RefreshDatabase;

    private const MISSING_ID = 999999;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Managers whose delete()/edit() previously dereferenced a null model and
     * raised a 500 when the record had already been removed.
     *
     * @return array<string, array{class-string}>
     */
    public static function managerProvider(): array
    {
        return [
            'announcement' => [AnnouncementManager::class],
            'article category' => [ArticleCategoryManager::class],
            'faq item' => [FaqItemManager::class],
            'homepage section' => [HomepageSectionManager::class],
            'menu' => [MenuManager::class],
            'product category' => [ProductCategoryManager::class],
            'site setting' => [SiteSettingManager::class],
        ];
    }

    /**
     * An uncaught null-property read fails this test, so reaching the
     * assertions at all proves the guard returned instead of fataling.
     */
    #[DataProvider('managerProvider')]
    public function test_editing_a_missing_record_is_a_no_op_instead_of_a_server_error(string $component): void
    {
        Livewire::actingAs($this->admin())
            ->test($component)
            ->call('edit', self::MISSING_ID)
            ->assertHasNoErrors()
            ->assertSet('editingId', null);
    }

    #[DataProvider('managerProvider')]
    public function test_deleting_a_missing_record_is_a_no_op_instead_of_a_server_error(string $component): void
    {
        Livewire::actingAs($this->admin())
            ->test($component)
            ->call('delete', self::MISSING_ID)
            ->assertHasNoErrors();
    }
}
