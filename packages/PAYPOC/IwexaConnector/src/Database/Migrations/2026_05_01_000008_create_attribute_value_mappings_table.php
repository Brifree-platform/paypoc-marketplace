<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypoc_attribute_value_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_value');
            $table->string('normalized_value')->nullable();
            $table->string('bagisto_value')->nullable();
            $table->unsignedBigInteger('attribute_mapping_id');
            $table->timestamps();

            $table->index('attribute_mapping_id');
            $table->foreign('attribute_mapping_id')->references('id')->on('paypoc_attribute_mappings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_attribute_value_mappings');
    }
};
