<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('attendee_count');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
