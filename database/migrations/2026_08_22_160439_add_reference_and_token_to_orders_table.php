<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('reference', 32)->nullable()->unique()->after('customer_phone');
            $table->string('token', 64)->nullable()->unique()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropUnique(['token']);
            $table->dropColumn(['reference', 'token']);
        });
    }
};