<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->string('product_name_snapshot');
            $table->unsignedBigInteger('unit_price_snapshot');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('final_price');
            $table->json('customization_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};