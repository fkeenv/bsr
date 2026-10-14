<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->uuid('submission_key')->nullable();
            $table->string('submission_hash', 64)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unique(['event_id', 'user_id', 'submission_key'], 'event_registration_submission_unique');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropUnique('event_registration_submission_unique');
            $table->dropColumn(['submission_key', 'submission_hash', 'confirmed_at']);
        });
    }
};
