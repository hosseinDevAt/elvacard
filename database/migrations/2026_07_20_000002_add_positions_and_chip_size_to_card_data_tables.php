<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_card_data', function (Blueprint $table) {
            $table->json('field_positions')->nullable()->after('expiry_date');
        });

        Schema::table('fuel_card_data', function (Blueprint $table) {
            $table->string('chip_size')->default('small')->after('chip_type'); // small or large
        });
    }

    public function down(): void
    {
        Schema::table('bank_card_data', function (Blueprint $table) {
            $table->dropColumn('field_positions');
        });

        Schema::table('fuel_card_data', function (Blueprint $table) {
            $table->dropColumn('chip_size');
        });
    }
};
