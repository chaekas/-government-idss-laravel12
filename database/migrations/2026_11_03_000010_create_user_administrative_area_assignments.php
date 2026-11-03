<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_administrative_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('administrative_area_id')->constrained()->restrictOnDelete();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'effective_to']);
            $table->index(['administrative_area_id', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_administrative_area');
    }
};
