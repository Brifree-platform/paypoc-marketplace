<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypoc_product_type_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_system');
            $table->string('source_product_type');
            $table->string('vendor_code')->nullable();
            $table->unsignedBigInteger('bagisto_attribute_family_id')->nullable();
            $table->string('google_product_category')->nullable();
            $table->string('google_product_type')->nullable();
            $table->string('amazon_product_type')->nullable();
            $table->enum('status', ['active', 'draft'])->default('draft');
            $table->timestamps();

            $table->unique(['source_system', 'source_product_type', 'vendor_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_product_type_mappings');
    }
};
