<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permission_changes', function (Blueprint $table): void {
            $table->string('action')->default('permissions');
            $table->string('previous_role_name')->nullable();
            $table->string('new_role_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('role_permission_changes', function (Blueprint $table): void {
            $table->dropColumn(['action', 'previous_role_name', 'new_role_name']);
        });
    }
};
