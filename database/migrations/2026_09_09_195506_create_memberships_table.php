<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('end_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ended_at']);
            $table->index(['property_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
