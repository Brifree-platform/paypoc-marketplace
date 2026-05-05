<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('paypoc_iwexa_vendors')) {
            Schema::create('paypoc_iwexa_vendors', function (Blueprint $table) {
                $table->id();
                $table->string('vendor_code')->unique();
                $table->string('vendor_name');
                $table->string('legal_name');
                $table->string('vat_number')->nullable();
                $table->string('tax_code')->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('province')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('country')->nullable();
                $table->string('website')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->json('responsible_person')->nullable();
                $table->json('original_iwexa_payload')->nullable();
                $table->timestamps();

                $table->index('vendor_code');
                $table->index('status');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_iwexa_vendors');
    }
};