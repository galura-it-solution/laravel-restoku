<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(
                ['user_id', 'status', 'notified_at', 'id'],
                'orders_user_status_notified_id_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_status_notified_id_index');
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });
    }
};
