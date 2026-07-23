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
        Schema::create('pasabuy_schedule_invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pasabuy_schedule_id')->unique()->constrained('pasabuy_schedules')->cascadeOnDelete();
            $table->foreignId('source_invoice_setting_id')->nullable()->constrained('pasabuy_seller_invoice_settings', 'id', 'sched_invoice_settings_source_id_foreign')->nullOnDelete();
            $table->decimal('markup_threshold_amount', 10, 2);
            $table->decimal('markup_percentage', 5, 2);
            $table->decimal('markup_fixed_amount', 10, 2);
            $table->unsignedSmallInteger('due_days');
            $table->enum('due_date_reference', ['delivery', 'schedule_finish']);
            $table->decimal('interest_rate', 5, 2);
            $table->enum('interest_period', ['day', 'week', 'month']);
            $table->enum('interest_base', ['outstanding_balance', 'full_total']);
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasabuy_schedule_invoice_settings');
    }
};
