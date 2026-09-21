<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('association_settings', function (Blueprint $table) {
            $table->string('letterhead_name')->nullable()->after('announcements_page_visibility');
            $table->string('letterhead_short_name')->nullable()->after('letterhead_name');
            $table->json('letterhead_address_lines')->nullable()->after('letterhead_short_name');
            $table->string('letterhead_contact')->nullable()->after('letterhead_address_lines');
            $table->string('letterhead_treasurer')->nullable()->after('letterhead_contact');
            $table->json('payment_channels')->nullable()->after('letterhead_treasurer');
        });

        $defaults = [
            'letterhead_name' => 'Blessed Sacrament Residences Homeowners Association',
            'letterhead_short_name' => 'BSR HOA',
            'letterhead_address_lines' => json_encode([
                'Blessed Sacrament Residences',
                'Quezon City, Metro Manila',
            ]),
            'letterhead_contact' => 'treasurer@bsr.example (placeholder)',
            'letterhead_treasurer' => 'Treasurer — Maria Santos (placeholder)',
            'payment_channels' => json_encode([
                [
                    'method' => 'Cash',
                    'detail' => 'Pay the Treasurer in person; ask for a handwritten receipt.',
                ],
                [
                    'method' => 'Bank transfer',
                    'detail' => 'BDO · Account name TBA · Account no. TBA',
                ],
                [
                    'method' => 'GCash',
                    'detail' => 'TBA — name the Property (Block + Lot) in the note.',
                ],
                [
                    'method' => 'Maya',
                    'detail' => 'TBA — name the Property (Block + Lot) in the note.',
                ],
            ]),
        ];

        DB::table('association_settings')->update($defaults);
    }

    public function down(): void
    {
        Schema::table('association_settings', function (Blueprint $table) {
            $table->dropColumn([
                'letterhead_name',
                'letterhead_short_name',
                'letterhead_address_lines',
                'letterhead_contact',
                'letterhead_treasurer',
                'payment_channels',
            ]);
        });
    }
};
