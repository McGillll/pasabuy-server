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
        Schema::create('pasabuy_schedule_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pasabuy_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('is_checked')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->unsignedInteger('additional_quantity')->default(0);
            $table->unsignedInteger('return_quantity')->default(0);
            $table->timestamps();

            $table->unique(['pasabuy_schedule_id', 'product_id'], 'schedule_checklist_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasabuy_schedule_checklist_items');
    }
};
