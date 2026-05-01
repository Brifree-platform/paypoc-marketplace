<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypoc_iwexa_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->indexed();
            $table->string('warehouse_code');
            $table->unsignedInteger('quantity');
            $table->string('source');
            $table->timestamp('created_at')->useCurrent();

            $table->index('sku');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_iwexa_stock_logs');
    }
};
