<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('samples') && !Schema::hasColumn('samples', 'packaged_at')) {
            Schema::table('samples', function (Blueprint $table) {
                $table->timestamp('packaged_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('samples') && Schema::hasColumn('samples', 'packaged_at')) {
            Schema::table('samples', function (Blueprint $table) {
                $table->dropColumn('packaged_at');
            });
        }
    }
};
