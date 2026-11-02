<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('disease_cases') && Schema::hasColumn('disease_cases', 'status')) {
            Schema::table('disease_cases', function (Blueprint $table) {
                $table->string('status', 40)->default('reported')->change();
            });
        }
        if (!Schema::hasTable('animals')) {
            Schema::create('animals', function (Blueprint $table) {
                $table->id();
                $table->string('animal_code')->unique();
                $table->foreignId('farm_id')->constrained()->restrictOnDelete();
                $table->string('species', 80)->index();
                $table->string('breed', 100)->nullable();
                $table->string('sex', 20)->default('unknown');
                $table->date('date_of_birth')->nullable();
                $table->string('identification_method', 100)->nullable();
                $table->string('external_identifier', 150)->nullable()->unique();
                $table->string('ownership_status', 40)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('investigations')) {
            Schema::create('investigations', function (Blueprint $table) {
                $table->id();
                $table->string('investigation_code')->unique();
                $table->foreignId('disease_case_id')->unique()->constrained()->restrictOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('started_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('investigated_at');
                $table->string('status', 40)->default('under_investigation')->index();
                $table->text('field_observations');
                $table->text('epidemiological_findings')->nullable();
                $table->text('recommended_actions')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('samples')) {
            Schema::table('samples', function (Blueprint $table) {
                if (!Schema::hasColumn('samples', 'barcode_value')) {
                    $table->string('barcode_value')->nullable()->unique();
                }
                if (!Schema::hasColumn('samples', 'collection_notes')) {
                    $table->text('collection_notes')->nullable();
                }
                if (!Schema::hasColumn('samples', 'received_at')) {
                    $table->timestamp('received_at')->nullable();
                }
                if (!Schema::hasColumn('samples', 'quality_status')) {
                    $table->string('quality_status', 40)->default('pending')->index();
                }
                if (!Schema::hasColumn('samples', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable();
                }
            });
        }

        if (!Schema::hasTable('sample_movements')) {
            Schema::create('sample_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
                $table->string('event_type', 50)->index();
                $table->string('from_location', 180)->nullable();
                $table->string('to_location', 180)->nullable();
                $table->string('condition_on_transfer', 180)->nullable();
                $table->string('received_by', 160)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('event_at')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('laboratory_test_results')) {
            Schema::create('laboratory_test_results', function (Blueprint $table) {
                $table->id();
                $table->string('result_code')->unique();
                $table->foreignId('sample_id')->constrained()->restrictOnDelete();
                $table->foreignId('laboratory_id')->constrained()->restrictOnDelete();
                $table->foreignId('assigned_technician')->constrained('users')->restrictOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('test_name', 180);
                $table->string('method', 180)->nullable();
                $table->text('observations')->nullable();
                $table->string('qualitative_result', 180)->nullable();
                $table->decimal('numeric_result', 14, 4)->nullable();
                $table->string('numeric_unit', 50)->nullable();
                $table->string('status', 40)->default('submitted_for_review')->index();
                $table->text('technician_comments')->nullable();
                $table->text('review_comments')->nullable();
                $table->timestamp('tested_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('outbreaks')) {
            Schema::create('outbreaks', function (Blueprint $table) {
                $table->id();
                $table->string('outbreak_code')->unique();
                $table->string('disease_name', 160)->index();
                $table->string('region', 100)->index();
                $table->string('district', 100)->index();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->date('start_date');
                $table->unsignedInteger('cases_count')->default(0);
                $table->unsignedInteger('deaths_count')->default(0);
                $table->string('severity', 20)->default('medium');
                $table->string('status', 40)->default('suspected')->index();
                $table->text('response_actions')->nullable();
                $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title', 180);
                $table->text('message');
                $table->string('category', 50)->default('general')->index();
                $table->timestamp('read_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 120)->index();
                $table->string('entity_type', 120)->nullable()->index();
                $table->string('entity_id', 80)->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('outbreaks');
        Schema::dropIfExists('laboratory_test_results');
        Schema::dropIfExists('sample_movements');
        if (Schema::hasTable('samples')) {
            Schema::table('samples', function (Blueprint $table) {
                foreach (['barcode_value', 'collection_notes', 'received_at', 'quality_status', 'rejection_reason'] as $column) {
                    if (Schema::hasColumn('samples', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
        Schema::dropIfExists('investigations');
        Schema::dropIfExists('animals');
    }
};
