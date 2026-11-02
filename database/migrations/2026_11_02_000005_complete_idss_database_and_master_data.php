<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Organization and authorization master data. The legacy users.role column
        // remains available during the transition; role_user supports multi-role users.
        Schema::create('organizations', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->string('organization_type', 80)->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id(); $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code')->unique(); $table->string('name'); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('label');
            $table->text('description')->nullable(); $table->boolean('is_system')->default(true);
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('label');
            $table->string('module', 100)->index(); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->id(); $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id(); $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete(); $table->timestamps();
            $table->unique(['permission_id', 'role_id']);
        });

        // Administrative boundaries are deliberately empty until the Ministry
        // supplies the authoritative boundary dataset and identifier scheme.
        Schema::create('administrative_areas', function (Blueprint $table) {
            $table->id(); $table->foreignId('parent_id')->nullable()->constrained('administrative_areas')->restrictOnDelete();
            $table->string('area_type', 30)->index(); $table->string('code', 80)->unique();
            $table->string('name'); $table->string('source_reference', 180)->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
            $table->index(['parent_id', 'area_type']);
        });

        Schema::create('offices', function (Blueprint $table) {
            $table->id(); $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('administrative_area_id')->nullable()->constrained('administrative_areas')->nullOnDelete();
            $table->string('code')->unique(); $table->string('name'); $table->string('office_type', 80)->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });

        // Farmer profile, contact/address records and joint ownership history.
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'organization_id')) $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('users', 'department_id')) $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('users', 'office_id')) $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('users', 'mobile_number')) $table->string('mobile_number', 40)->nullable()->index();
            if (!Schema::hasColumn('users', 'archived_at')) $table->timestamp('archived_at')->nullable()->index();
        });
        Schema::table('farmers', function (Blueprint $table) {
            if (!Schema::hasColumn('farmers', 'sex')) $table->string('sex', 20)->nullable();
            if (!Schema::hasColumn('farmers', 'date_of_birth')) $table->date('date_of_birth')->nullable();
            if (!Schema::hasColumn('farmers', 'nationality')) $table->string('nationality', 100)->nullable();
            if (!Schema::hasColumn('farmers', 'farmer_type')) $table->string('farmer_type', 40)->default('individual')->index();
            if (!Schema::hasColumn('farmers', 'status')) $table->string('status', 30)->default('active')->index();
            if (!Schema::hasColumn('farmers', 'archived_at')) $table->timestamp('archived_at')->nullable()->index();
        });
        Schema::create('farmer_contacts', function (Blueprint $table) {
            $table->id(); $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->string('contact_type', 40); $table->string('contact_value', 180);
            $table->boolean('is_primary')->default(false); $table->boolean('is_verified')->default(false);
            $table->timestamps(); $table->index(['farmer_id', 'contact_type']);
        });
        Schema::create('farmer_addresses', function (Blueprint $table) {
            $table->id(); $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('administrative_area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address_type', 40)->default('physical'); $table->string('address_line', 255)->nullable();
            $table->string('region', 100)->nullable(); $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable(); $table->string('village', 100)->nullable();
            $table->boolean('is_primary')->default(false); $table->timestamps();
            $table->index(['region', 'district', 'ward', 'village']);
        });

        // Farm types and farmer/farm relationship support single and joint ownership.
        Schema::create('farm_types', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name')->unique();
            $table->text('description')->nullable(); $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0); $table->timestamps(); $table->softDeletes();
        });
        Schema::table('farms', function (Blueprint $table) {
            if (!Schema::hasColumn('farms', 'farm_type_id')) $table->foreignId('farm_type_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('farms', 'farm_size')) $table->decimal('farm_size', 14, 3)->nullable();
            if (!Schema::hasColumn('farms', 'farm_size_unit')) $table->string('farm_size_unit', 30)->nullable();
            if (!Schema::hasColumn('farms', 'administrative_area_id')) $table->foreignId('administrative_area_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('farms', 'status')) $table->string('status', 30)->default('active')->index();
            if (!Schema::hasColumn('farms', 'archived_at')) $table->timestamp('archived_at')->nullable()->index();
        });
        Schema::create('farmer_farm', function (Blueprint $table) {
            $table->id(); $table->foreignId('farmer_id')->constrained()->restrictOnDelete();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->string('ownership_type', 40)->default('owner'); $table->decimal('ownership_share', 6, 3)->nullable();
            $table->date('effective_from')->nullable(); $table->date('effective_to')->nullable();
            $table->boolean('is_primary')->default(false); $table->timestamps();
            $table->index(['farmer_id', 'effective_to']); $table->index(['farm_id', 'effective_to']);
        });
        Schema::create('farmer_ownership_history', function (Blueprint $table) {
            $table->id(); $table->foreignId('farmer_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 50); $table->text('details')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at'); $table->timestamps();
        });

        // Animal master data, identifiers and traceability events.
        Schema::create('species', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name')->unique();
            $table->text('description')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('breeds', function (Blueprint $table) {
            $table->id(); $table->foreignId('species_id')->constrained()->restrictOnDelete();
            $table->string('code'); $table->string('name'); $table->text('description')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
            $table->unique(['species_id', 'code']); $table->unique(['species_id', 'name']);
        });
        Schema::table('animals', function (Blueprint $table) {
            if (!Schema::hasColumn('animals', 'farmer_id')) $table->foreignId('farmer_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('animals', 'species_id')) $table->foreignId('species_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('animals', 'breed_id')) $table->foreignId('breed_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('animals', 'colour')) $table->string('colour', 80)->nullable();
            if (!Schema::hasColumn('animals', 'date_of_death')) $table->date('date_of_death')->nullable();
            if (!Schema::hasColumn('animals', 'disposal_status')) $table->string('disposal_status', 40)->nullable();
            if (!Schema::hasColumn('animals', 'archived_at')) $table->timestamp('archived_at')->nullable()->index();
        });
        Schema::create('animal_identifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('animal_id')->constrained()->restrictOnDelete();
            $table->string('identification_type', 40); $table->string('identifier_value', 180)->unique();
            $table->string('status', 30)->default('active')->index(); $table->date('issued_at')->nullable();
            $table->date('retired_at')->nullable(); $table->foreignId('replaced_by_id')->nullable()->constrained('animal_identifications')->nullOnDelete();
            $table->timestamps(); $table->index(['animal_id', 'status']);
        });
        Schema::create('animal_movements', function (Blueprint $table) {
            $table->id(); $table->foreignId('animal_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->foreignId('to_farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->string('movement_reason', 80)->nullable(); $table->timestamp('moved_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable(); $table->timestamps(); $table->index(['animal_id', 'moved_at']);
        });
        Schema::create('animal_ownership_transfers', function (Blueprint $table) {
            $table->id(); $table->foreignId('animal_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_farmer_id')->nullable()->constrained('farmers')->nullOnDelete();
            $table->foreignId('to_farmer_id')->constrained('farmers')->restrictOnDelete();
            $table->date('transferred_at'); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable(); $table->timestamps(); $table->index(['animal_id', 'transferred_at']);
        });

        // Vaccination, disease classification and animal health history.
        Schema::create('vaccination_campaigns', function (Blueprint $table) {
            $table->id(); $table->string('campaign_code')->unique(); $table->string('name');
            $table->foreignId('administrative_area_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date')->nullable(); $table->date('end_date')->nullable();
            $table->string('status', 30)->default('planned')->index(); $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id(); $table->foreignId('animal_id')->constrained()->restrictOnDelete();
            $table->foreignId('vaccination_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vaccine_name', 180); $table->string('batch_number', 120)->nullable();
            $table->date('vaccinated_at'); $table->foreignId('vaccinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dose', 80)->nullable(); $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['animal_id', 'vaccinated_at']);
        });
        Schema::create('disease_categories', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name')->unique();
            $table->text('description')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('disease_symptoms', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name')->unique();
            $table->text('description')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('animal_disease_records', function (Blueprint $table) {
            $table->id(); $table->foreignId('animal_id')->constrained()->restrictOnDelete();
            $table->foreignId('disease_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disease_name', 180); $table->date('observed_at')->nullable();
            $table->string('health_status', 40)->nullable(); $table->text('treatment')->nullable();
            $table->text('notes')->nullable(); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(); $table->index(['animal_id', 'observed_at']);
        });
        Schema::table('disease_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('disease_cases', 'animal_id')) $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('disease_cases', 'disease_category_id')) $table->foreignId('disease_category_id')->nullable()->constrained()->nullOnDelete();
            if (!Schema::hasColumn('disease_cases', 'administrative_area_id')) $table->foreignId('administrative_area_id')->nullable()->constrained()->nullOnDelete();
        });

        // Laboratory and sample reference/master data.
        Schema::create('laboratory_sections', function (Blueprint $table) {
            $table->id(); $table->foreignId('laboratory_id')->constrained()->cascadeOnDelete();
            $table->string('code'); $table->string('name'); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->unique(['laboratory_id', 'code']);
        });
        Schema::create('laboratory_tests', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->foreignId('disease_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('specimen_requirements', 255)->nullable(); $table->boolean('is_active')->default(true);
            $table->text('description')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('test_methods', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->string('standard_reference', 180)->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('sample_types', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name')->unique();
            $table->text('description')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('sample_transportations', function (Blueprint $table) {
            $table->id(); $table->foreignId('sample_id')->constrained()->restrictOnDelete();
            $table->string('consignment_reference', 120)->nullable(); $table->string('courier_name', 160)->nullable();
            $table->string('vehicle_reference', 120)->nullable(); $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable(); $table->string('temperature_condition', 120)->nullable();
            $table->text('notes')->nullable(); $table->timestamps(); $table->index(['sample_id', 'dispatched_at']);
        });
        Schema::create('sample_storage_records', function (Blueprint $table) {
            $table->id(); $table->foreignId('sample_id')->constrained()->restrictOnDelete();
            $table->string('storage_location', 180); $table->string('storage_condition', 120)->nullable();
            $table->timestamp('stored_at'); $table->timestamp('removed_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('quality_control_records', function (Blueprint $table) {
            $table->id(); $table->foreignId('laboratory_id')->constrained()->restrictOnDelete();
            $table->foreignId('laboratory_test_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sample_id')->nullable()->constrained()->nullOnDelete();
            $table->string('qc_type', 80); $table->string('outcome', 40)->index();
            $table->text('findings')->nullable(); $table->text('corrective_action')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at'); $table->timestamps();
        });
        Schema::create('laboratory_equipment', function (Blueprint $table) {
            $table->id(); $table->foreignId('laboratory_id')->constrained()->restrictOnDelete();
            $table->string('equipment_code')->unique(); $table->string('name');
            $table->string('manufacturer', 120)->nullable(); $table->string('model', 120)->nullable();
            $table->string('serial_number', 120)->nullable(); $table->date('last_calibrated_at')->nullable();
            $table->date('calibration_due_at')->nullable(); $table->string('status', 40)->default('active')->index();
            $table->timestamps(); $table->softDeletes();
        });

        // Extend existing laboratory results to link a result to its catalogue test.
        Schema::table('laboratory_test_results', function (Blueprint $table) {
            if (!Schema::hasColumn('laboratory_test_results', 'laboratory_test_id')) $table->foreignId('laboratory_test_id')->nullable()->constrained('laboratory_tests')->nullOnDelete();
            if (!Schema::hasColumn('laboratory_test_results', 'test_method_id')) $table->foreignId('test_method_id')->nullable()->constrained('test_methods')->nullOnDelete();
        });

        // Location history and the remaining database entities named by SRS Chapter 6.
        Schema::create('farm_locations', function (Blueprint $table) {
            $table->id(); $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->foreignId('administrative_area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location_type', 40)->default('primary'); $table->string('address_line', 255)->nullable();
            $table->string('region', 100)->nullable(); $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable(); $table->string('village', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable(); $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false); $table->timestamps();
            $table->index(['farm_id', 'is_primary']);
        });
        Schema::create('laboratory_user', function (Blueprint $table) {
            $table->id(); $table->foreignId('laboratory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assignment_role', 60)->nullable(); $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable(); $table->timestamps();
            $table->unique(['laboratory_id', 'user_id', 'effective_from']);
        });
        Schema::create('laboratory_test_assignments', function (Blueprint $table) {
            $table->id(); $table->foreignId('sample_id')->constrained()->restrictOnDelete();
            $table->foreignId('laboratory_test_id')->constrained()->restrictOnDelete();
            $table->foreignId('laboratory_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_technician')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('assigned')->index();
            $table->timestamp('assigned_at')->nullable(); $table->timestamp('completed_at')->nullable();
            $table->timestamps(); $table->index(['sample_id', 'status']);
        });
        Schema::table('laboratory_test_results', function (Blueprint $table) {
            if (!Schema::hasColumn('laboratory_test_results', 'laboratory_test_assignment_id')) {
                $table->foreignId('laboratory_test_assignment_id')->nullable()->constrained('laboratory_test_assignments')->nullOnDelete();
            }
        });
        Schema::create('disease_case_symptom', function (Blueprint $table) {
            $table->id(); $table->foreignId('disease_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disease_symptom_id')->constrained()->restrictOnDelete();
            $table->timestamps(); $table->unique(['disease_case_id', 'disease_symptom_id']);
        });
        Schema::create('outbreak_case', function (Blueprint $table) {
            $table->id(); $table->foreignId('outbreak_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disease_case_id')->constrained()->restrictOnDelete();
            $table->timestamps(); $table->unique(['outbreak_id', 'disease_case_id']);
        });
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->string('report_type', 80); $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id(); $table->foreignId('report_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path', 500)->nullable(); $table->string('file_format', 20)->nullable();
            $table->json('parameters')->nullable(); $table->timestamp('generated_at');
            $table->timestamps(); $table->index(['report_definition_id', 'generated_at']);
        });
        Schema::create('dashboard_definitions', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->string('audience_role', 80)->nullable(); $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->longText('value')->nullable();
            $table->string('value_type', 40)->default('string'); $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Consistent audit actor/status columns on major business tables.
        foreach (['farmers', 'farms', 'animals', 'disease_cases', 'investigations', 'samples', 'laboratories', 'outbreaks'] as $name) {
            if (Schema::hasTable($name)) {
                Schema::table($name, function (Blueprint $table) use ($name) {
                    if (!Schema::hasColumn($name, 'created_by')) $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                    if (!Schema::hasColumn($name, 'modified_by')) $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
                    if (!Schema::hasColumn($name, 'record_status')) $table->string('record_status', 30)->default('active')->index();
                });
            }
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive: business records and master data may already
        // depend on these tables. Rollback is handled by a forward corrective migration.
    }
};