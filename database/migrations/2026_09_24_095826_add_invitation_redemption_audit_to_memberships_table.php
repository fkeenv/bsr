<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->foreignId('property_invitation_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('terms_of_service_version_id')
                ->nullable()
                ->constrained('legal_document_versions')
                ->restrictOnDelete();
            $table->foreignId('privacy_policy_version_id')
                ->nullable()
                ->constrained('legal_document_versions')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('privacy_policy_version_id');
            $table->dropConstrainedForeignId('terms_of_service_version_id');
            $table->dropConstrainedForeignId('property_invitation_id');
        });
    }
};
