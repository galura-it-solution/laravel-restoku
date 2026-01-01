<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'updated_at'], 'orders_status_updated_index');
            $table->index(['restaurant_table_id', 'updated_at'], 'orders_table_updated_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_updated_index');
            $table->dropIndex('orders_table_updated_index');
        });
    }
};
