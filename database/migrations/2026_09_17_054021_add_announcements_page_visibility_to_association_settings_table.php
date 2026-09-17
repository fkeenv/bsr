<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('association_settings', function (Blueprint $table) {
            $table->string('announcements_page_visibility')->default('private')->after('levy_day_of_month');
        });
    }

    public function down(): void
    {
        Schema::table('association_settings', function (Blueprint $table) {
            $table->dropColumn('announcements_page_visibility');
        });
    }
};
