<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->string('animal_code')->unique();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->string('species')->default('poultry');
            $table->string('breed')->nullable();
            $table->string('sex')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('identification_method')->nullable();
            $table->string('external_identifier')->nullable()->index();
            $table->string('ownership_status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('investigations', function (Blueprint $table) {
            $table->id();
            $table->string('investigation_code')->unique();
            $table->foreignId('disease_case_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('investigated_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('field_observations')->nullable();
            $table->text('epidemiological_findings')->nullable();
            $table->text('recommended_actions')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('sample_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('condition_on_transfer')->nullable();
            $table->string('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('event_at');
            $table->timestamps();
        });

        Schema::create('laboratory_test_results', function (Blueprint $table) {
            $table->id();
            $table->string('result_code')->unique();
            $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
            $table->foreignId('laboratory_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_technician')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('test_name');
            $table->string('method')->nullable();
            $table->text('observations')->nullable();
            $table->string('qualitative_result')->nullable();
            $table->decimal('numeric_result', 14, 4)->nullable();
            $table->string('numeric_unit')->nullable();
            $table->string('status')->default('assigned');
            $table->text('technician_comments')->nullable();
            $table->text('review_comments')->nullable();
            $table->dateTime('tested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('outbreaks', function (Blueprint $table) {
            $table->id();
            $table->string('outbreak_code')->unique();
            $table->string('disease_name');
            $table->string('region');
            $table->string('district');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('start_date');
            $table->unsignedInteger('cases_count')->default(0);
            $table->unsignedInteger('deaths_count')->default(0);
            $table->string('severity')->default('medium');
            $table->string('status')->default('suspected');
            $table->text('response_actions')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('category')->default('general');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->string('quality_status')->default('pending')->after('transport_status');
            $table->string('rejection_reason')->nullable()->after('quality_status');
            $table->string('barcode_value')->nullable()->unique()->after('sample_code');
            $table->timestamp('received_at')->nullable()->after('collection_date');
            $table->text('collection_notes')->nullable()->after('sample_type');
        });
    }

    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->dropUnique(['barcode_value']);
            $table->dropColumn(['quality_status', 'rejection_reason', 'barcode_value', 'received_at', 'collection_notes']);
        });
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('outbreaks');
        Schema::dropIfExists('laboratory_test_results');
        Schema::dropIfExists('sample_movements');
        Schema::dropIfExists('investigations');
        Schema::dropIfExists('animals');
    }
};
