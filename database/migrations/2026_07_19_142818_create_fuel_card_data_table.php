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
        Schema::create('fuel_card_data', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name');
            $table->string('car_model');
            $table->string('vin_number');
            $table->string('sys_number');
            $table->string('chip_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_card_data');
    }
};
