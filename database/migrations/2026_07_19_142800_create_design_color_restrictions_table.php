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
        Schema::create('design_color_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_image_id')->constrained('design_images')->cascadeOnDelete();
            $table->foreignId('forbidden_card_color_id')->constrained('colors')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_color_restrictions');
    }
};
