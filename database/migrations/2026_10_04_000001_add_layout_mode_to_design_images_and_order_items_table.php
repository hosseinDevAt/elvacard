<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_images', function (Blueprint $table) {
            $table->string('layout_mode', 20)->default('centered')->after('image_path');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('layout_mode_snapshot', 20)->default('centered')->after('design_image_path_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('layout_mode_snapshot');
        });

        Schema::table('design_images', function (Blueprint $table) {
            $table->dropColumn('layout_mode');
        });
    }
};
