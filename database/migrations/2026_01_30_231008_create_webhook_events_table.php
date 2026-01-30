<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');

            $table->string('provider');                 // stripe, shopify, typeform, etc.
            $table->string('provider_event_id');        // id original del proveïdor
            $table->string('type')->nullable();         // tipus (original o normalitzat)

            $table->json('raw_payload');
            $table->json('normalized_payload')->nullable();

            $table->string('status')->default('received'); // received|processed|failed|dead_letter
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
