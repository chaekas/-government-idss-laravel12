<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The required tables and sample columns are already handled
        // by 2026_11_02_000001_add_srs_surveillance_workflows.php.
    }

    public function down(): void
    {
        // Intentionally empty: do not delete existing surveillance data.
    }
};
