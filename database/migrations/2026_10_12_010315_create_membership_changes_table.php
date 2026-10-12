<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->constrained('users');
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->json('previous_state');
            $table->json('new_state');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_changes');
    }
};
