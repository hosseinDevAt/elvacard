<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Decouple product_color_prices from required product_id:
        // Add customization_workflow, make product_id nullable, and add unique constraint per workflow.
        Schema::table('product_color_prices', function (Blueprint $table) {
            if (! Schema::hasColumn('product_color_prices', 'customization_workflow')) {
                $table->string('customization_workflow', 32)->nullable()->after('product_id')->index();
            }
            $table->foreignId('product_id')->nullable()->change();
            $table->unique(['customization_workflow', 'color_id'], 'pcp_workflow_color_unique');
        });

        // Backfill: if existing prices belong to a product with customization_workflow,
        // copy that workflow to the price row and detach from the product.
        if (Schema::hasColumn('products', 'customization_workflow')) {
            $cardPrices = DB::table('product_color_prices')
                ->join('products', 'product_color_prices.product_id', '=', 'products.id')
                ->whereNotNull('products.customization_workflow')
                ->select('product_color_prices.id', 'products.customization_workflow')
                ->get();

            foreach ($cardPrices as $row) {
                DB::table('product_color_prices')
                    ->where('id', $row->id)
                    ->update([
                        'customization_workflow' => $row->customization_workflow,
                        'product_id' => null,
                    ]);
            }
        }

        // 2. Allow order_items to reference workflow without requiring a Store product:
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
        });

        // 3. Allow product_images to belong directly to a workflow without requiring a Store product:
        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'customization_workflow')) {
                $table->string('customization_workflow', 32)->nullable()->after('product_id')->index();
            }
            $table->foreignId('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'customization_workflow')) {
                $table->dropColumn('customization_workflow');
            }
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('product_color_prices', function (Blueprint $table) {
            $table->dropUnique('pcp_workflow_color_unique');
            if (Schema::hasColumn('product_color_prices', 'customization_workflow')) {
                $table->dropColumn('customization_workflow');
            }
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
