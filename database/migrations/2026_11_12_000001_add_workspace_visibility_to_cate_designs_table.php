<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cate_designs', function (Blueprint $table) {
            $table->string('workspace_visibility')->nullable()->default('both')->index()->after('is_active');
        });

        // Ensure any existing historical categories explicitly have 'both'
        DB::table('cate_designs')
            ->whereNull('workspace_visibility')
            ->orWhere('workspace_visibility', '')
            ->update(['workspace_visibility' => 'both']);
    }

    public function down(): void
    {
        Schema::table('cate_designs', function (Blueprint $table) {
            $table->dropIndex(['workspace_visibility']);
            $table->dropColumn('workspace_visibility');
        });
    }
};
