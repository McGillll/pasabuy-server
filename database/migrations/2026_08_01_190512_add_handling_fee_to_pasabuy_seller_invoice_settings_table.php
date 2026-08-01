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
        Schema::table('pasabuy_seller_invoice_settings', function (Blueprint $table) {
            $table->decimal('handling_fee', 10, 2)->default(0.00)->after('markup_fixed_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasabuy_seller_invoice_settings', function (Blueprint $table) {
            $table->dropColumn('handling_fee');
        });
    }
};
