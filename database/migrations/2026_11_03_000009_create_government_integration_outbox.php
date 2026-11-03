<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 60)->index();
            $table->string('channel', 30)->index();
            $table->string('event_type', 100)->index();
            $table->string('recipient', 255)->nullable();
            $table->string('subject', 180)->nullable();
            $table->json('payload');
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->timestamp('available_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->string('external_reference', 180)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at']);
        });

        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 60)->index();
            $table->string('operation', 100);
            $table->string('status', 30)->index();
            $table->string('external_reference', 180)->nullable();
            $table->json('request_metadata')->nullable();
            $table->json('response_metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('integration_outbox');
    }
};
