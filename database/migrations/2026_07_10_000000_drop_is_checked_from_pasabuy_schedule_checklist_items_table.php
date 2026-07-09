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
        if (Schema::hasColumn('pasabuy_schedule_checklist_items', 'is_checked')) {
            Schema::table('pasabuy_schedule_checklist_items', function (Blueprint $table) {
                $table->dropColumn('is_checked');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasabuy_schedule_checklist_items', function (Blueprint $table) {
            $table->boolean('is_checked')->default(false)->after('quantity');
        });
    }
};
