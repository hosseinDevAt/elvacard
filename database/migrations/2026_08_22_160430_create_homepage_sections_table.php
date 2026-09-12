<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_type')->index();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->json('settings')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};