<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('disease_cases', 'reported_at')) $table->timestamp('reported_at')->nullable()->index();
            if (!Schema::hasColumn('disease_cases', 'affected_species')) $table->string('affected_species', 120)->nullable();
            if (!Schema::hasColumn('disease_cases', 'latitude')) $table->decimal('latitude', 10, 7)->nullable();
            if (!Schema::hasColumn('disease_cases', 'longitude')) $table->decimal('longitude', 10, 7)->nullable();
            if (!Schema::hasColumn('disease_cases', 'location_description')) $table->string('location_description', 255)->nullable();
            if (!Schema::hasColumn('disease_cases', 'reporting_channel')) $table->string('reporting_channel', 40)->nullable();
        });

        Schema::table('investigations', function (Blueprint $table) {
            if (!Schema::hasColumn('investigations', 'possible_source')) $table->text('possible_source')->nullable();
            if (!Schema::hasColumn('investigations', 'transmission_route')) $table->text('transmission_route')->nullable();
            if (!Schema::hasColumn('investigations', 'biosecurity_observations')) $table->text('biosecurity_observations')->nullable();
            if (!Schema::hasColumn('investigations', 'immediate_control_measures')) $table->text('immediate_control_measures')->nullable();
            if (!Schema::hasColumn('investigations', 'approved_by')) $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('investigations', 'approved_at')) $table->timestamp('approved_at')->nullable();
            if (!Schema::hasColumn('investigations', 'closed_at')) $table->timestamp('closed_at')->nullable();
        });

        Schema::table('outbreaks', function (Blueprint $table) {
            if (!Schema::hasColumn('outbreaks', 'disease_category_id')) $table->foreignId('disease_category_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('outbreaks', 'administrative_area_id')) $table->foreignId('administrative_area_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('outbreaks', 'confirmed_at')) $table->timestamp('confirmed_at')->nullable();
            if (!Schema::hasColumn('outbreaks', 'closed_at')) $table->timestamp('closed_at')->nullable();
            if (!Schema::hasColumn('outbreaks', 'modified_by')) $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('disease_case_status_history', function (Blueprint $table) {
            $table->id(); $table->foreignId('disease_case_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 40)->nullable(); $table->string('to_status', 40);
            $table->text('reason')->nullable(); $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->index(); $table->timestamps();
            $table->index(['disease_case_id', 'changed_at']);
        });

        Schema::create('investigation_histories', function (Blueprint $table) {
            $table->id(); $table->foreignId('investigation_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 60)->index(); $table->json('previous_values')->nullable();
            $table->json('new_values')->nullable(); $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->index(); $table->timestamps();
            $table->index(['investigation_id', 'changed_at']);
        });

        Schema::create('disease_case_attachments', function (Blueprint $table) {
            $table->id(); $table->foreignId('disease_case_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name', 255); $table->string('file_path', 500);
            $table->string('mime_type', 120); $table->unsignedBigInteger('file_size');
            $table->timestamp('uploaded_at'); $table->timestamps();
            $table->index(['disease_case_id', 'uploaded_at']);
        });

        Schema::create('investigation_attachments', function (Blueprint $table) {
            $table->id(); $table->foreignId('investigation_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name', 255); $table->string('file_path', 500);
            $table->string('mime_type', 120); $table->unsignedBigInteger('file_size');
            $table->timestamp('uploaded_at'); $table->timestamps();
            $table->index(['investigation_id', 'uploaded_at']);
        });

        Schema::create('surveillance_alert_rules', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->string('severity', 30)->default('high'); $table->unsignedInteger('case_threshold')->default(1);
            $table->unsignedSmallInteger('within_hours')->default(24);
            $table->boolean('notify_field_officers')->default(true);
            $table->boolean('notify_regional_officers')->default(true);
            $table->boolean('notify_ministry_officers')->default(true);
            $table->boolean('is_active')->default(true); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(); $table->softDeletes();
        });
    }

    public function down(): void
    {
        // Preserve operational surveillance records during rollback.
    }
};
