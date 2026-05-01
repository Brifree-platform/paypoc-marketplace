<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypoc_iwexa_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('delivery_id')->unique();
            $table->string('event_type');
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'failed'])->default('received');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('event_id');
            $table->unique('delivery_id');
            $table->index('event_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypoc_iwexa_webhook_events');
    }
};
