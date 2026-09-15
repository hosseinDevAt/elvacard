<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['gateway', 'transaction_id']);
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->index('route_key');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway', 'transaction_id']);
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex(['route_key']);
        });
    }
};
