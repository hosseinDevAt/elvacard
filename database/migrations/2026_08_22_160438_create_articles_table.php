<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('article_category_id')
                ->nullable()
                ->constrained('article_categories')
                ->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('cover_image')->nullable();
            $table->string('status')->index();
            $table->timestamp('published_at')->nullable()->index();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('robots_index')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};