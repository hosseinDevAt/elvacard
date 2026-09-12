<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::create('colors',function(Blueprint $table){$table->id();$table->string('name',100)->index();$table->string('code_hex',7)->nullable();$table->string('preview_image')->nullable();$table->boolean('is_active')->default(true)->index();$table->integer('sort_order')->default(0)->index();$table->timestamps();});}public function down():void{Schema::dropIfExists('colors');}};
