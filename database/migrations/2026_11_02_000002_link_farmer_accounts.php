<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'farmer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('farmer_id')->nullable()->unique()->constrained('farmers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'farmer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['farmer_id']);
                $table->dropUnique(['farmer_id']);
                $table->dropColumn('farmer_id');
            });
        }
    }
};
