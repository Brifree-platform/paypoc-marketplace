<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypoc_iwexa_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('parent_sku')->nullable()->indexed();
            $table->string('item_group_id')->nullable();
            $table->string('ean')->nullable();
            $table->string('iwexa_product_id')->nullable();
            $table->string('vendor_code')->indexed();
            $table->string('product_type')->nullable()->indexed();
            $table->string('source_category')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('pending_mapping')->default(false)->indexed();
            $table->json('original_iwexa_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('sku');
            $table->index('vendor_code');
            $table->index('parent_sku');
            $table->index('pending_mapping');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_iwexa_products');
    }
};
