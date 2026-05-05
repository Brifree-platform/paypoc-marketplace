<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypoc_iwexa_routing_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code');
            $table->string('sku');
            $table->string('warehouse_code');
            $table->enum('fulfillment_type', ['central', 'vendor']);
            $table->string('origin_country', 2);
            $table->string('destination_country', 2);
            $table->string('destination_postal_code')->nullable();
            $table->integer('quantity');
            $table->decimal('product_weight_kg', 10, 3)->nullable();
            $table->integer('product_volume_cm3')->nullable();
            $table->string('shipping_method');
            $table->integer('shipping_cost_cents');
            $table->string('currency', 3);
            $table->integer('preparation_time_min_days');
            $table->integer('preparation_time_max_days');
            $table->integer('delivery_min_days');
            $table->integer('delivery_max_days');
            $table->integer('final_delivery_min_days');
            $table->integer('final_delivery_max_days');
            $table->json('original_request_payload')->nullable();
            $table->json('calculated_response_payload')->nullable();
            $table->timestamps();

            $table->index(['sku', 'created_at']);
            $table->index('vendor_code');
            $table->index('warehouse_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypoc_iwexa_routing_quotes');
    }
};