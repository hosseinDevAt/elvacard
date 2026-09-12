<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('shipping_address')->after('notes')->nullable();
            $table->string('shipping_postal_code', 20)->after('shipping_address')->nullable();
            $table->string('shipping_plaque', 30)->after('shipping_postal_code')->nullable();
            $table->text('shipping_description')->after('shipping_plaque')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_address', 'shipping_postal_code', 'shipping_plaque', 'shipping_description']);
        });
    }
};