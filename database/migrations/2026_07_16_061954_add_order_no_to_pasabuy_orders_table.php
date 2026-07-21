<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pasabuy_orders', function (Blueprint $table) {
            $table->string('order_no')->nullable()->after('uuid');
        });

        DB::table('pasabuy_orders')
            ->where(function ($query) {
                $query->whereNull('order_no')->orWhere('order_no', '');
            })
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) {
                do {
                    $orderNo = sprintf('PSBY-%s-%s', now()->format('Ymd'), Str::upper(Str::random(6)));
                } while (DB::table('pasabuy_orders')->where('order_no', $orderNo)->exists());

                DB::table('pasabuy_orders')->where('id', $id)->update(['order_no' => $orderNo]);
            });

        Schema::table('pasabuy_orders', function (Blueprint $table) {
            $table->unique('order_no');
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
