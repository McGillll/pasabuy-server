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
            $table->enum('channel', ['customer', 'seller'])->default('customer')->after('user_id');
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('customer_contact')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasabuy_orders', function (Blueprint $table) {
            $table->dropColumn('channel');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->string('customer_contact')->nullable(false)->change();
        });
    }
};
