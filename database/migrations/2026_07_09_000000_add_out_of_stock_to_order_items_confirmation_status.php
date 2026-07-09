<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE order_items MODIFY confirmation_status ENUM('none', 'pending', 'accepted', 'removed', 'out_of_stock') DEFAULT 'none'");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("UPDATE order_items SET confirmation_status = 'removed' WHERE confirmation_status = 'out_of_stock'");
        DB::statement("ALTER TABLE order_items MODIFY confirmation_status ENUM('none', 'pending', 'accepted', 'removed') DEFAULT 'none'");
    }
};
