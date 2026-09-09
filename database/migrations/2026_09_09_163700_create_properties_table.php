<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('block');
            $table->string('lot');
            $table->string('street_address')->nullable();
            $table->string('recorded_owner_name')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->timestamp('opening_balance_frozen_at')->nullable();
            $table->timestamp('first_charged_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['block', 'lot']);
            $table->index(['block', 'lot', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
