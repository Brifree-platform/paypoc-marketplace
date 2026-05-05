<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypoc_iwexa_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->unique();
            $table->string('vendor_code')->nullable();
            $table->string('name');
            $table->enum('type', ['central', 'vendor']);
            $table->string('country', 2);
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('original_iwexa_payload')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('vendor_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypoc_iwexa_warehouses');
    }
};