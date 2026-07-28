<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pasabuy_order_items', function (Blueprint $table) {
            $table->dropForeign(['pasabuy_schedule_product_id']);
            $table->foreign('pasabuy_schedule_product_id')->references('id')->on('pasabuy_schedule_products')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasabuy_order_items', function (Blueprint $table) {
            $table->dropForeign(['pasabuy_schedule_product_id']);
            $table->foreign('pasabuy_schedule_product_id')->references('id')->on('pasabuy_schedule_products')->nullOnDelete();
        });
    }
};
