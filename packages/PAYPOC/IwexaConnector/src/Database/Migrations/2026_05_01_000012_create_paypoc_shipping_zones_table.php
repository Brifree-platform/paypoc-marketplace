<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypoc_shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('origin_country', 2);
            $table->string('destination_country', 2);
            $table->string('name')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['origin_country', 'destination_country'], 'pp_ship_zone_origin_dest_unique');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypoc_shipping_zones');
    }
};