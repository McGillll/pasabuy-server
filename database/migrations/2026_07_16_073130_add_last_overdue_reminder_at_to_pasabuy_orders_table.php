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
            $table->timestamp('last_overdue_reminder_at')->nullable()->after('invoice_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasabuy_orders', function (Blueprint $table) {
            $table->dropColumn('last_overdue_reminder_at');
        });
    }
};
