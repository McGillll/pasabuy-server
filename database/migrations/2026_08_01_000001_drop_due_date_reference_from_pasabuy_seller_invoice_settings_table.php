<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasabuy_seller_invoice_settings', function (Blueprint $table) {
            $table->dropColumn(['due_days', 'due_date_reference']);
        });
    }

    public function down(): void
    {
        Schema::table('pasabuy_seller_invoice_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('due_days')->default(15);
            $table->enum('due_date_reference', ['delivery', 'schedule_finish'])->default('delivery');
        });
    }
};
