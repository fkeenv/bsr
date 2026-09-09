<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('starts_year');
            $table->unsignedTinyInteger('starts_month');
            $table->unsignedSmallInteger('ends_year')->nullable();
            $table->unsignedTinyInteger('ends_month')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'fee_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspends');
    }
};
