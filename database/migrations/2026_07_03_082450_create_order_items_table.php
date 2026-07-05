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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('estimated_price', 10, 2);
            $table->decimal('actual_price', 10, 2)->nullable();
            $table->boolean('is_bonus')->default(false);
            $table->enum('confirmation_status', ['none', 'pending', 'accepted', 'removed'])->default('none');
            $table->decimal('max_price', 10, 2)->nullable();
            $table->boolean('auto_buy_if_under_max')->default(false);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
