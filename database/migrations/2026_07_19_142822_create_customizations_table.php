<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_type_id')->constrained('card_types')->cascadeOnDelete();
            $table->foreignId('design_image_id')->nullable()->constrained('design_images')->nullOnDelete();
            $table->morphs('customizable'); // ساخت خودکار id و type برای ارتباط پلی‌مورفیک
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customizations');
    }
};
