<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasabuy_schedules', function (Blueprint $table) {
            $table->date('pasabuy_schedule_date')->nullable()->after('cutoff_at');
        });
    }

    public function down(): void
    {
        Schema::table('pasabuy_schedules', function (Blueprint $table) {
            $table->dropColumn('pasabuy_schedule_date');
        });
    }
};
