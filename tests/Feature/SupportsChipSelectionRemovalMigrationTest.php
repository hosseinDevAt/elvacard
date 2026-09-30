<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

/**
 * N-Onyx-51 F4 - migration verification for the single destructive schema change.
 *
 * Unlike the additive index migration of N-Onyx-46, this one drops a column, so
 * the test is deliberately paranoid: it proves the drop is scoped to that single
 * column, that existing product rows survive untouched, that down() restores the
 * original definition, and that both directions are idempotent on an already
 * migrated schema.
 *
 * It also pins the model metadata, because a stray $fillable or $casts entry
 * pointing at a dropped column is a latent write error that no schema assertion
 * would catch.
 */
class SupportsChipSelectionRemovalMigrationTest extends TestCase
{
    private const MIGRATION = 'migrations/2026_09_30_000002_drop_supports_chip_selection_from_products_table.php';

    public function test_the_migration_drops_only_the_flag_and_preserves_product_rows(): void
    {
        $this->assertTrue($this->hasFlagColumn(), 'Precondition: the column must exist before up().');

        $before = $this->productRows();

        $this->runMigration('up');

        $this->assertFalse(
            $this->hasFlagColumn(),
            'up() must remove products.supports_chip_selection.',
        );
        $this->assertSame(
            $before,
            $this->productRows(),
            'Dropping the flag must not modify or drop any product row.',
        );
        $this->assertSame(['id', 'name'], $this->productColumns(), 'No other column may be touched.');
    }

    public function test_down_restores_the_original_column_definition(): void
    {
        $this->runMigration('up');
        $this->assertFalse($this->hasFlagColumn());

        $this->runMigration('down');

        $this->assertTrue($this->hasFlagColumn(), 'down() must restore the column.');

        DB::table('products')->insert(['name' => 'inserted-after-rollback']);

        $inserted = DB::table('products')->where('name', 'inserted-after-rollback')->first();

        $this->assertNotNull($inserted);
        $this->assertSame(
            0,
            (int) $inserted->supports_chip_selection,
            'The restored column must default to false, matching the original definition.',
        );
    }

    public function test_both_directions_are_idempotent_on_an_already_migrated_schema(): void
    {
        $this->runMigration('up');
        $this->runMigration('up');
        $this->assertFalse($this->hasFlagColumn(), 'A repeated up() must be a no-op.');

        $this->runMigration('down');
        $this->runMigration('down');
        $this->assertTrue($this->hasFlagColumn(), 'A repeated down() must be a no-op.');
    }

    public function test_a_down_up_cycle_leaves_product_data_identical(): void
    {
        $before = $this->productRows();

        $this->runMigration('up');
        $this->runMigration('down');
        $this->runMigration('up');

        $this->assertFalse($this->hasFlagColumn());
        $this->assertSame(
            $before,
            $this->productRows(),
            'A down/up cycle must leave product data identical.',
        );
    }

    public function test_the_model_no_longer_declares_the_dropped_column(): void
    {
        $model = new ReflectionClass(Product::class);
        $instance = $model->newInstance();

        $this->assertNotContains(
            'supports_chip_selection',
            $instance->getFillable(),
            'A $fillable entry for a dropped column would break mass assignment.',
        );

        $casts = $model->getProperty('casts');
        $casts->setAccessible(true);

        $this->assertArrayNotHasKey(
            'supports_chip_selection',
            $casts->getValue($instance),
            'A $casts entry for a dropped column would break every Product::create().',
        );
    }

    private function hasFlagColumn(): bool
    {
        return Schema::hasColumn('products', 'supports_chip_selection');
    }

    /**
     * @return list<string>
     */
    private function productColumns(): array
    {
        return Schema::getColumnListing('products');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productRows(): array
    {
        return DB::table('products')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function runMigration(string $direction): void
    {
        $path = database_path(self::MIGRATION);

        $this->assertFileExists($path);

        $migration = require $path;

        $this->assertInstanceOf(Migration::class, $migration);

        $migration->{$direction}();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('supports_chip_selection')->default(false);
        });

        DB::table('products')->insert(['name' => 'product-a', 'supports_chip_selection' => true]);
        DB::table('products')->insert(['name' => 'product-b', 'supports_chip_selection' => false]);
        DB::table('products')->insert(['name' => 'product-c', 'supports_chip_selection' => true]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('products');

        parent::tearDown();
    }
}
