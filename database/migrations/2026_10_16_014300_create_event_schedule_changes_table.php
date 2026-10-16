<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_schedule_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('changed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('old_starts_at');
            $table->dateTime('old_ends_at');
            $table->dateTime('old_registration_deadline');
            $table->dateTime('new_starts_at');
            $table->dateTime('new_ends_at');
            $table->dateTime('new_registration_deadline');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_schedule_changes');
    }
};
