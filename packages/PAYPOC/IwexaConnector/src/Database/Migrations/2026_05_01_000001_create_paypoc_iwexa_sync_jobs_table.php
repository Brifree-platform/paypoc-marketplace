<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypoc_iwexa_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['catalog_import', 'stock_update', 'order_push', 'content_sync'])->indexed();
            $table->enum('status', ['pending', 'processing', 'failed', 'completed'])->default('pending');
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_iwexa_sync_jobs');
    }
};
