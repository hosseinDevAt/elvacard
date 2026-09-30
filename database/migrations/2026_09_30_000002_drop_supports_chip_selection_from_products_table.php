<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the vestigial products.supports_chip_selection flag.
 *
 * The column was created in 2026_08_22_160422 and was only ever written by the
 * ProductManager admin form. A full repository inventory proved it had no
 * runtime consumer: no query scope, no service, no storefront/customizer view,
 * no API or serialization path, and no test ever read it. Fuel chip-size
 * selection is determined exclusively by
 * customization_workflow = fuel_card (FuelCardCustomization::VALID_CHIP_SIZES
 * and CartService::sanitizeCustomization), so the checkbox only ever implied a
 * configurability the code never honoured.
 *
 * The field is guarded by hasColumn() so the migration is a no-op on a schema
 * that has already been migrated, and the original definition is restored on
 * rollback. It is destructive for the stored booleans only; no other column,
 * index, or row is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'supports_chip_selection')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('supports_chip_selection');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'supports_chip_selection')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('supports_chip_selection')->default(false);
        });
    }
};
