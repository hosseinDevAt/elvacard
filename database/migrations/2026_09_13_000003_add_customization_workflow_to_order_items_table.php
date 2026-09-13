<?php

use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('customization_workflow')->nullable()->index()->after('product_id');
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        $items = DB::table('order_items')->select('id', 'customization_json')->get();

        foreach ($items as $item) {
            $customization = json_decode((string) $item->customization_json, true);
            $customization = is_array($customization) ? $customization : [];

            DB::table('order_items')->where('id', $item->id)->update([
                'customization_workflow' => CustomizationWorkflowRegistry::classifyLegacyCustomization($customization)?->value,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('customization_workflow');
        });
    }
};
