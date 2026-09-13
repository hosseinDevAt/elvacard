<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable()->after('product_id');
            $table->string('color_name_snapshot')->nullable()->after('color_id');
            $table->unsignedBigInteger('design_id')->nullable()->after('color_name_snapshot');
            $table->string('design_name_snapshot')->nullable()->after('design_id');
            $table->unsignedBigInteger('design_image_id')->nullable()->after('design_name_snapshot');
            $table->string('design_image_path_snapshot')->nullable()->after('design_image_id');
        });

        $this->backfill();

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('color_id')->references('id')->on('colors')->nullOnDelete();
            $table->foreign('design_id')->references('id')->on('designs')->nullOnDelete();
            $table->foreign('design_image_id')->references('id')->on('design_images')->nullOnDelete();
        });
    }

    private function backfill(): void
    {
        $items = DB::table('order_items')->select('id', 'customization_json')->get();

        foreach ($items as $item) {
            $customization = json_decode((string) $item->customization_json, true);
            $customization = is_array($customization) ? $customization : [];

            $colorId = isset($customization['color_id']) ? (int) $customization['color_id'] : null;
            $designId = isset($customization['design_id']) ? (int) $customization['design_id'] : null;
            $designImageId = isset($customization['design_image_id']) ? (int) $customization['design_image_id'] : null;

            $colorName = null;
            if ($colorId !== null && $colorId > 0) {
                $color = DB::table('colors')->where('id', $colorId)->first();
                if ($color) {
                    $colorName = $color->name;
                } else {
                    $colorId = null;
                }
            }

            $designName = null;
            if ($designId !== null && $designId > 0) {
                $design = DB::table('designs')->where('id', $designId)->first();
                if ($design) {
                    $designName = $design->name;
                } else {
                    $designId = null;
                }
            }

            $designImagePath = null;
            if ($designImageId !== null && $designImageId > 0) {
                $image = DB::table('design_images')->where('id', $designImageId)->first();
                if ($image) {
                    $designImagePath = $image->image_path;
                } else {
                    $designImageId = null;
                }
            }

            DB::table('order_items')->where('id', $item->id)->update([
                'color_id' => $colorId,
                'color_name_snapshot' => $colorName,
                'design_id' => $designId,
                'design_name_snapshot' => $designName,
                'design_image_id' => $designImageId,
                'design_image_path_snapshot' => $designImagePath,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
            $table->dropForeign(['design_id']);
            $table->dropForeign(['design_image_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'color_id',
                'color_name_snapshot',
                'design_id',
                'design_name_snapshot',
                'design_image_id',
                'design_image_path_snapshot',
            ]);
        });
    }
};
