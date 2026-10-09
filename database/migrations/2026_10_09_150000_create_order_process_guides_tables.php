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
        Schema::create('order_process_guides', function (Blueprint $table) {
            $table->id();
            $table->string('workflow', 32)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('order_process_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')
                ->constrained('order_process_guides')
                ->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('duration_hint', 100)->nullable();
            $table->string('icon', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['guide_id', 'sort_order']);
            $table->index(['guide_id', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_process_stages');
        Schema::dropIfExists('order_process_guides');
    }
};
