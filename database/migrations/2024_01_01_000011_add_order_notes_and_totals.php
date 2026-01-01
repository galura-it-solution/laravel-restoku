<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('note')->nullable()->after('status');
            $table->unsignedBigInteger('subtotal')->default(0)->after('note');
            $table->unsignedBigInteger('service_charge')->default(0)->after('subtotal');
            $table->unsignedBigInteger('tax')->default(0)->after('service_charge');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['note', 'subtotal', 'service_charge', 'tax']);
        });
    }
};
