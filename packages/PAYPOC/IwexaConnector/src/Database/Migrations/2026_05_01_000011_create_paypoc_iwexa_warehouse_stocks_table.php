<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypoc_iwexa_warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code');
            $table->string('sku');
            $table->string('vendor_code');
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->integer('preparation_time_min_days')->default(0);
            $table->integer('preparation_time_max_days')->default(0);
            $table->json('original_iwexa_payload')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_code', 'sku'], 'iwexa_wh_stock_wh_sku_unique');
            $table->index('vendor_code');
            $table->index('sku');
            $table->index('available_quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypoc_iwexa_warehouse_stocks');
    }
};