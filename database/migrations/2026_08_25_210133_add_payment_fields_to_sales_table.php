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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('total');
            $table->decimal('exchange_rate', 10, 4)->nullable()->after('payment_method');
            $table->decimal('amount_usd', 10, 2)->nullable()->after('exchange_rate');
            $table->string('receipt_path')->nullable()->after('amount_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'exchange_rate', 'amount_usd', 'receipt_path']);
        });
    }
};
