<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('link')->nullable();
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('start_date')->nullable()->index();
            $table->dateTime('end_date')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};