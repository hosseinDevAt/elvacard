<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('card_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('account_name')->nullable();
            $table->text('instruction_message')->nullable();
            $table->text('success_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_payment_settings');
    }
};