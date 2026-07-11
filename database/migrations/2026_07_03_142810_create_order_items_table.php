<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('pasabuy_order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('pasabuy_orders')->cascadeOnDelete();
            $table->foreignId('pasabuy_schedule_product_id')->nullable()->constrained('pasabuy_schedule_products')->nullOnDelete();
            $table->string('product_name');
            $table->decimal('estimated_price', 10, 2);
            $table->decimal('actual_price', 10, 2)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('bonus_quantity')->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('pasabuy_order_items');
    }
};
