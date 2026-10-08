<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('visibility')->default('private');
        });

        $visibility = DB::table('association_settings')->value('announcements_page_visibility') ?? 'private';
        DB::table('announcements')->update(['visibility' => $visibility]);
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
