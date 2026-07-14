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
        Schema::create('pasabuy_seller_invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('markup_threshold_amount', 10, 2)->default(1000.00);
            $table->decimal('markup_percentage', 5, 2)->default(10.00);
            $table->decimal('markup_fixed_amount', 10, 2)->default(100.00);
            $table->unsignedSmallInteger('due_days')->default(15);
            $table->enum('due_date_reference', ['delivery', 'schedule_finish'])->default('delivery');
            $table->decimal('interest_rate', 5, 2)->default(1.00);
            $table->enum('interest_period', ['day', 'week', 'month'])->default('month');
            $table->enum('interest_base', ['outstanding_balance', 'full_total'])->default('outstanding_balance');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasabuy_seller_invoice_settings');
    }
};
