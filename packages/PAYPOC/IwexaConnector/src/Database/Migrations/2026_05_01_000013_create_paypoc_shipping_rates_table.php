<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypoc_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_zone_id');
            $table->decimal('min_weight_kg', 10, 3)->default(0);
            $table->decimal('max_weight_kg', 10, 3);
            $table->integer('min_volume_cm3')->nullable();
            $table->integer('max_volume_cm3')->nullable();
            $table->integer('price_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('carrier')->nullable();
            $table->string('shipping_method');
            $table->integer('delivery_min_days');
            $table->integer('delivery_max_days');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('shipping_zone_id')->references('id')->on('paypoc_shipping_zones')->onDelete('cascade');
            $table->index('shipping_zone_id');
            $table->index('status');
            $table->index(['min_weight_kg', 'max_weight_kg']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypoc_shipping_rates');
    }
};