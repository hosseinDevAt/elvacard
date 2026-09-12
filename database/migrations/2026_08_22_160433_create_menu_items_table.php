<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')
                ->constrained('menus')
                ->cascadeOnDelete();

            $table->string('item_type')->index();
            $table->string('title');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('custom_url')->nullable();
            $table->string('target')->default('_self');
            $table->json('settings')->nullable();
            $table->index('menu_id');
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};