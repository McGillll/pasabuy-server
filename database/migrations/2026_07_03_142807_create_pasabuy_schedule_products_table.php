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
        Schema::create('pasabuy_schedule_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pasabuy_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('pasabuy_products')->cascadeOnDelete();
            $table->decimal('actual_price', 10, 2)->nullable();
            $table->enum('product_status', ['none', 'b1t1', 'out_of_stock'])->default('none');
            $table->timestamp('price_confirmed_at')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->timestamps();
            $table->unique(['pasabuy_schedule_id', 'product_id'], 'schedule_products_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('pasabuy_schedule_products');
    }
};
