<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_role_assignment_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('previous_assignments');
            $table->json('new_assignments');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_role_assignment_changes');
    }
};
