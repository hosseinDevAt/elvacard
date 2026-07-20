<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_types', function (Blueprint $table) {
            $table->string('type')->after('id')->default('bank'); // 'bank' or 'fuel'
        });
    }

    public function down(): void
    {
        Schema::table('card_types', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
