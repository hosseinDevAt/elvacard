<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('customization_workflow')->nullable()->index()->after('type');
        });

        // Historical type -> workflow mapping. type stays a pure taxonomy and
        // never drives runtime behavior; customization_workflow is the axis
        // that the product page and the cart actually branch on.
        DB::table('products')->where('type', 'bank')->update(['customization_workflow' => 'bank_card']);
        DB::table('products')->where('type', 'fuel')->update(['customization_workflow' => 'fuel_card']);
        DB::table('products')->where('type', 'standard')->update(['customization_workflow' => null]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('customization_workflow');
        });
    }
};
