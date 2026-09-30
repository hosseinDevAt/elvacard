<?php

namespace Tests\Feature;

use App\Services\StoredFileManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * N-Onyx-46 — migration verification for the single additive schema change.
 *
 * Proves the index migration is safe in both directions and, above all, that
 * it preserves every existing historical row: a durability ticket must never
 * be able to lose order data.
 */
class DesignImagePathSnapshotIndexMigrationTest extends TestCase
{
    public function test_the_migration_creates_the_index_without_touching_existing_rows(): void
    {
        $before = $this->orderItemsWithPaths();

        $this->assertFalse($this->hasPathSnapshotIndex(), 'Precondition: the index must not exist yet.');

        $this->runMigration('up');

        $this->assertTrue(
            $this->hasPathSnapshotIndex(),
            'The migration must index order_items.design_image_path_snapshot.',
        );
        $this->assertSame(
            $before,
            $this->orderItemsWithPaths(),
            'Adding the index must not modify or drop any historical order row.',
        );
    }

    public function test_the_migration_is_reversible_and_reappliable(): void
    {
        $before = $this->orderItemsWithPaths();

        $this->runMigration('up');
        $this->assertTrue($this->hasPathSnapshotIndex());

        $this->runMigration('down');
        $this->assertFalse($this->hasPathSnapshotIndex(), 'down() must remove the index.');

        $this->runMigration('up');
        $this->assertTrue($this->hasPathSnapshotIndex(), 'The migration must be re-appliable.');

        $this->assertSame(
            $before,
            $this->orderItemsWithPaths(),
            'A down/up cycle must leave historical order data identical.',
        );
    }

    public function test_the_indexed_guard_still_finds_historical_references(): void
    {
        $this->runMigration('up');

        $referenced = $this->orderItemsWithPaths()[0]['design_image_path_snapshot'];

        // The guard must keep working with the index in place, including for a
        // row whose path is NULL (which the guard must treat as unreferenced).
        $this->assertTrue(app(StoredFileManager::class)->isDesignImageFileReferenced($referenced));
        $this->assertFalse(
            app(StoredFileManager::class)->isDesignImageFileReferenced('designs/absent.png'),
        );
    }

    public function test_null_paths_do_not_make_the_guard_match_unrelated_files(): void
    {
        $this->runMigration('up');

        $this->assertTrue(
            collect($this->orderItemsWithPaths())
                ->contains(fn (array $row): bool => $row['design_image_path_snapshot'] === null),
            'Precondition: a NULL path row must exist in this fixture.',
        );

        $manager = app(StoredFileManager::class);

        $this->assertFalse($manager->isDesignImageFileReferenced(''));
        $this->assertFalse($manager->isDesignImageFileReferenced('designs/never-referenced.png'));
    }

    private function hasPathSnapshotIndex(): bool
    {
        return collect(Schema::getIndexes('order_items'))
            ->contains(fn (array $index): bool => in_array('design_image_path_snapshot', $index['columns'] ?? [], true));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function orderItemsWithPaths(): array
    {
        return DB::table('order_items')
            ->orderBy('id')
            ->get(['id', 'design_image_path_snapshot'])
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function runMigration(string $direction): void
    {
        $path = database_path('migrations/2026_09_30_000001_add_design_image_path_snapshot_index_to_order_items_table.php');

        $this->assertFileExists($path);

        $migration = require $path;

        $this->assertInstanceOf(Migration::class, $migration);

        $migration->{$direction}();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Fixture rows, including one legacy row with no snapshotted path and
        // one where the path was cleared after the fact.
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->string('design_image_path_snapshot')->nullable();
        });

        // The file guard checks the live catalog table first, so the fixture
        // needs it to exercise the guard with the index in place.
        Schema::create('design_images', function (Blueprint $table): void {
            $table->id();
            $table->string('image_path');
        });

        foreach (['designs/historic-a.png', 'designs/historic-b.png', null] as $index => $path) {
            DB::table('order_items')->insert([
                'design_image_path_snapshot' => $path,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('design_images');

        parent::tearDown();
    }
}
