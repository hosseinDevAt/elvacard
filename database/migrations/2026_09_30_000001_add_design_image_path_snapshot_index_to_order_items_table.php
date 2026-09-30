<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The design-image file reference guard
 * (App\Services\StoredFileManager::isDesignImageFileReferenced) runs on every
 * admin design-image delete and replacement. It answers
 * "does any historical order still point at this exact file?" with an existence
 * query on order_items.design_image_path_snapshot, which had no index and was
 * therefore a full scan of the fastest-growing table in the system, on an
 * interactive admin path.
 *
 * The column is nullable and the guard already treats a NULL path as
 * "unreferenced", so a plain index is sufficient and no partial/functional
 * index (which would not be portable across the supported drivers) is needed.
 *
 * Purely additive: it creates an index only, preserves all existing rows, and
 * is backward compatible with every prior code version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->index('design_image_path_snapshot', 'order_items_design_image_path_snapshot_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex('order_items_design_image_path_snapshot_index');
        });
    }
};
