<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->boolean('is_popular')->default(false)->index()->after('is_active');
            $table->integer('popular_sort_order')->default(0)->index()->after('is_popular');
        });
    }

    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropIndex(['is_popular']);
            $table->dropIndex(['popular_sort_order']);
            $table->dropColumn(['is_popular', 'popular_sort_order']);
        });
    }
};
