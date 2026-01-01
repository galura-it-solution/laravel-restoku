<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('menu_name')->nullable()->after('menu_id');
            $table->text('menu_description')->nullable()->after('menu_name');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['menu_name', 'menu_description']);
        });
    }
};
