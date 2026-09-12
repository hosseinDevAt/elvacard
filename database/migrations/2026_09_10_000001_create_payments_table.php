<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->string('method');
            $table->string('status');
            $table->unsignedBigInteger('amount');
            $table->string('tracking_code')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('gateway')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};