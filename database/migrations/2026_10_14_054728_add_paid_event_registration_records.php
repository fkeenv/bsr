<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->text('payment_instructions')->nullable();
        });
        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->string('status')->default('confirmed');
            $table->unsignedInteger('price_centavos')->default(0);
            $table->unsignedBigInteger('total_centavos')->default(0);
            $table->text('payment_instructions')->nullable();
            $table->dateTime('expires_at')->nullable()->index();
            $table->dateTime('expired_at')->nullable();
        });
        DB::table('event_registrations')->whereNotNull('cancelled_at')->update(['status' => 'cancelled']);
        Schema::table('event_attendees', function (Blueprint $table): void {
            $table->string('ticket_token', 64)->nullable()->change();
        });
        Schema::create('event_payment_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_registration_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_centavos');
            $table->string('method');
            $table->string('reference', 1000);
            $table->string('receipt_path')->nullable();
            $table->string('status')->default('submitted');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('event_cancellation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_registration_id')->constrained()->restrictOnDelete();
            $table->uuid('submission_key');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_reason')->nullable();
            $table->timestamps();
            $table->unique(['event_registration_id', 'submission_key']);
        });
        Schema::create('event_refund_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_payment_record_id')->constrained()->restrictOnDelete();
            $table->uuid('submission_key');
            $table->unsignedBigInteger('amount_centavos');
            $table->string('method');
            $table->string('reference', 1000);
            $table->text('reason');
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('refunded_at');
            $table->timestamps();
            $table->unique(['event_payment_record_id', 'submission_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_refund_records');
        Schema::dropIfExists('event_cancellation_requests');
        Schema::dropIfExists('event_payment_records');
        // Pending attendees have no issued ticket. Preserve them when rolling back.
        DB::table('event_attendees')->whereNull('ticket_token')->orderBy('id')->each(function ($attendee): void {
            DB::table('event_attendees')->where('id', $attendee->id)->update(['ticket_token' => Str::random(64)]);
        });
        Schema::table('event_attendees', function (Blueprint $table): void {
            $table->string('ticket_token', 64)->nullable(false)->change();
        });
        Schema::table('event_registrations', function (Blueprint $table): void {
            $table->dropColumn(['status', 'price_centavos', 'total_centavos', 'payment_instructions', 'expires_at', 'expired_at']);
        });
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('payment_instructions');
        });
    }
};
