<?php

namespace Tests\Feature;

use App\Models\CateDesign;
use App\Models\Design;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PopularDesignsDataLayerTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CateDesign::create([
            'name' => 'تست',
            'slug' => 'test-cat',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_migration_adds_popular_columns_with_defaults(): void
    {
        $this->assertTrue(Schema::hasColumn('designs', 'is_popular'));
        $this->assertTrue(Schema::hasColumn('designs', 'popular_sort_order'));

        $design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح ساده',
            'slug' => 'simple-design',
            'is_active' => true,
        ]);

        $design->refresh();
        $this->assertFalse($design->is_popular);
        $this->assertSame(0, $design->popular_sort_order);
    }

    public function test_migration_is_reversible_and_reappliable(): void
    {
        $migrationFile = database_path('migrations/2026_10_09_000001_add_popular_fields_to_designs_table.php');
        $this->assertFileExists($migrationFile);

        /** @var Migration $migration */
        $migration = require $migrationFile;
        $this->assertInstanceOf(Migration::class, $migration);

        // Reverse migration
        $migration->down();
        $this->assertFalse(Schema::hasColumn('designs', 'is_popular'));
        $this->assertFalse(Schema::hasColumn('designs', 'popular_sort_order'));

        // Reapply migration
        $migration->up();
        $this->assertTrue(Schema::hasColumn('designs', 'is_popular'));
        $this->assertTrue(Schema::hasColumn('designs', 'popular_sort_order'));
    }

    public function test_design_model_casts_and_mass_assignment(): void
    {
        $design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح پرطرفدار',
            'slug' => 'popular-design-test',
            'is_popular' => true,
            'popular_sort_order' => 5,
            'is_active' => true,
        ]);

        $this->assertTrue($design->is_popular);
        $this->assertIsBool($design->is_popular);
        $this->assertSame(5, $design->popular_sort_order);
        $this->assertIsInt($design->popular_sort_order);
    }

    public function test_popular_scope_filters_correctly(): void
    {
        $popularDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح پرطرفدار ۱',
            'slug' => 'popular-1',
            'is_popular' => true,
            'popular_sort_order' => 1,
            'is_active' => true,
        ]);

        $regularDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح معمولی',
            'slug' => 'regular-1',
            'is_popular' => false,
            'popular_sort_order' => 0,
            'is_active' => true,
        ]);

        $results = Design::query()->popular()->pluck('id')->all();

        $this->assertContains($popularDesign->id, $results);
        $this->assertNotContains($regularDesign->id, $results);
    }
}
