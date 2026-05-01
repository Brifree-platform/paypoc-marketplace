<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypoc_attribute_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_type_mapping_id');
            $table->string('source_attribute_code');
            $table->string('bagisto_attribute_code')->nullable();
            $table->unsignedBigInteger('bagisto_attribute_id')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('variant_axis')->default(false);
            $table->boolean('searchable')->default(false);
            $table->boolean('filterable')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('unit_mapping')->nullable();
            $table->json('value_mapping')->nullable();
            $table->enum('status', ['active', 'draft'])->default('draft');
            $table->timestamps();

            $table->unique(['product_type_mapping_id', 'source_attribute_code']);
            $table->foreign('product_type_mapping_id')->references('id')->on('paypoc_product_type_mappings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_attribute_mappings');
    }
};
