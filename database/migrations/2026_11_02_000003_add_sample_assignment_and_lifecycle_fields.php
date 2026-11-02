<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('samples')) {
            return;
        }

        Schema::table('samples', function (Blueprint $table) {
            if (!Schema::hasColumn('samples', 'laboratory_reference_number')) {
                $table->string('laboratory_reference_number', 100)->nullable()->unique();
            }
            if (!Schema::hasColumn('samples', 'laboratory_section')) {
                $table->string('laboratory_section', 120)->nullable();
            }
            if (!Schema::hasColumn('samples', 'assigned_technician')) {
                $table->foreignId('assigned_technician')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('samples', 'assigned_by')) {
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('samples', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }
            if (!Schema::hasColumn('samples', 'testing_started_at')) {
                $table->timestamp('testing_started_at')->nullable();
            }
            if (!Schema::hasColumn('samples', 'testing_completed_at')) {
                $table->timestamp('testing_completed_at')->nullable();
            }
            if (!Schema::hasColumn('samples', 'released_at')) {
                $table->timestamp('released_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('samples')) {
            return;
        }

        Schema::table('samples', function (Blueprint $table) {
            foreach (['laboratory_reference_number', 'laboratory_section', 'assigned_technician', 'assigned_by', 'assigned_at', 'testing_started_at', 'testing_completed_at', 'released_at'] as $column) {
                if (Schema::hasColumn('samples', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
