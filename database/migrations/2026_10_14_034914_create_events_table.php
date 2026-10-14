<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('image_path')->nullable();
            $table->string('venue');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('registration_deadline');
            $table->string('audience')->default('public');
            $table->string('status')->default('draft');
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('price_centavos')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'audience', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
