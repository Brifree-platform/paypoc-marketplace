<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypoc_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_category')->indexed();
            $table->unsignedBigInteger('paypoc_category_id')->nullable();
            $table->unsignedBigInteger('bagisto_category_id')->nullable();
            $table->string('google_product_category')->nullable();
            $table->string('product_type')->nullable();
            $table->string('vendor_code')->nullable()->indexed();
            $table->boolean('override')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('source_category');
            $table->index('vendor_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_category_mappings');
    }
};
