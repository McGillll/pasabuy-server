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
        Schema::table('pasabuy_orders', function (Blueprint $table) {
            $table->string('order_no')->unique()->after('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasabuy_orders', function (Blueprint $table) {
            $table->dropColumn('order_no');
        });
    }
};
