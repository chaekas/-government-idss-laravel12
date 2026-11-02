<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('field_officer');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_code')->unique();
            $table->string('full_name');
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('national_id')->nullable()->index();
            $table->string('region')->index();
            $table->string('district')->index();
            $table->string('ward')->nullable();
            $table->string('village')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->string('farm_code')->unique();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('region')->index();
            $table->string('district')->index();
            $table->string('ward')->nullable();
            $table->string('village')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('poultry_type')->nullable();
            $table->unsignedInteger('bird_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('disease_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_code')->unique();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->string('disease_suspected');
            $table->date('date_observed');
            $table->text('symptoms');
            $table->unsignedInteger('birds_affected');
            $table->unsignedInteger('birds_dead');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['reported', 'under_investigation', 'confirmed', 'closed'])->default('reported');
            $table->text('investigation_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('level')->default('regional');
            $table->string('region')->nullable();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('sample_code')->unique();
            $table->foreignId('disease_case_id')->constrained()->restrictOnDelete();
            $table->foreignId('laboratory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('collection_date')->nullable();
            $table->string('sample_type');
            $table->enum('transport_status', ['not_dispatched', 'in_transit', 'received'])->default('not_dispatched');
            $table->enum('test_status', ['pending', 'in_progress', 'completed', 'approved'])->default('pending');
            $table->text('result_summary')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
        Schema::dropIfExists('laboratories');
        Schema::dropIfExists('disease_cases');
        Schema::dropIfExists('farms');
        Schema::dropIfExists('farmers');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
