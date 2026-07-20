<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_card_data', function (Blueprint $table) {
            $table->string('plate_number')->nullable()->after('sys_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('card_types', function (Blueprint $table) {
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('fuel_card_data', function (Blueprint $table) {
            $table->dropColumn('plate_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('card_types', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });
    }
};
