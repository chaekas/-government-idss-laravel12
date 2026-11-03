<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'disease_cases' => [
            'cases_status_date_idx' => ['status', 'date_observed'],
            'cases_area_status_idx' => ['administrative_area_id', 'status'],
        ],
        'samples' => [
            'samples_lab_quality_transport_idx' => ['laboratory_id', 'quality_status', 'transport_status'],
        ],
        'laboratory_test_results' => [
            'lab_results_lab_status_idx' => ['laboratory_id', 'status', 'created_at'],
        ],
        'sample_movements' => [
            'sample_movements_sample_event_idx' => ['sample_id', 'event_at'],
        ],
        'user_notifications' => [
            'notifications_user_read_created_idx' => ['user_id', 'read_at', 'created_at'],
        ],
        'audit_logs' => [
            'audit_logs_user_created_idx' => ['user_id', 'created_at'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($indexes as $indexName => $columns) {
                $columnsExist = collect($columns)->every(
                    fn (string $column): bool => Schema::hasColumn($tableName, $column)
                );

                if ($columnsExist && !Schema::hasIndex($tableName, $indexName)) {
                    Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                        $table->index($columns, $indexName);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            foreach (array_keys($indexes) as $indexName) {
                if (Schema::hasIndex($tableName, $indexName)) {
                    Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }
    }
};
