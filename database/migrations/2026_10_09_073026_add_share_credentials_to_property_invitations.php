<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_invitations', function (Blueprint $table): void {
            $table->string('code_hash', 64)->nullable()->unique();
            $table->text('share_token')->nullable();
            $table->text('share_code')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('property_invitations', function (Blueprint $table): void {
            $table->dropUnique(['code_hash']);
            $table->dropColumn(['code_hash', 'share_token', 'share_code']);
        });
    }
};
