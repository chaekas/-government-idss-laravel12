<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_equipment_calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_equipment_id')->constrained('laboratory_equipment')->restrictOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('calibration_date');
            $table->date('due_date')->nullable();
            $table->string('outcome', 30)->index();
            $table->string('certificate_reference', 180)->nullable();
            $table->text('findings')->nullable();
            $table->text('corrective_action')->nullable();
            $table->timestamps();
            $table->index(['laboratory_equipment_id', 'calibration_date']);
        });

        Schema::create('laboratory_corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action_code', 80)->unique();
            $table->foreignId('laboratory_id')->constrained()->restrictOnDelete();
            $table->foreignId('quality_control_record_id')->nullable()->constrained('quality_control_records')->nullOnDelete();
            $table->foreignId('laboratory_equipment_id')->nullable()->constrained('laboratory_equipment')->nullOnDelete();
            $table->foreignId('equipment_calibration_id')->nullable()->constrained('laboratory_equipment_calibrations')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 20)->default('medium')->index();
            $table->string('status', 30)->default('open')->index();
            $table->text('description');
            $table->date('due_date')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['laboratory_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_corrective_actions');
        Schema::dropIfExists('laboratory_equipment_calibrations');
    }
};
