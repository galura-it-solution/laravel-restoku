<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->integer('stock')->nullable()->after('is_active');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['stock']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['notes']);
        });
    }
};
