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
        // MySQL keeps DDL from a failed migration. Resume without recreating existing schema or history.
        if (! Schema::hasColumn('events', 'payment_instructions')) {
            Schema::table('events', function (Blueprint $table): void {
                $table->text('payment_instructions')->nullable();
            });
        }
        Schema::table('event_registrations', function (Blueprint $table): void {
            if (! Schema::hasColumn('event_registrations', 'status')) {
                $table->string('status')->default('confirmed');
            }
            if (! Schema::hasColumn('event_registrations', 'price_centavos')) {
                $table->unsignedInteger('price_centavos')->default(0);
            }
            if (! Schema::hasColumn('event_registrations', 'total_centavos')) {
                $table->unsignedBigInteger('total_centavos')->default(0);
            }
            if (! Schema::hasColumn('event_registrations', 'payment_instructions')) {
                $table->text('payment_instructions')->nullable();
            }
            if (! Schema::hasColumn('event_registrations', 'expires_at')) {
                $table->dateTime('expires_at')->nullable();
            }
            if (! Schema::hasColumn('event_registrations', 'expired_at')) {
                $table->dateTime('expired_at')->nullable();
            }
        });
        if (! Schema::hasIndex('event_registrations', ['expires_at'])) {
            Schema::table('event_registrations', function (Blueprint $table): void {
                $table->index('expires_at');
            });
        }
        DB::table('event_registrations')->where('status', 'confirmed')->whereNotNull('cancelled_at')->update(['status' => 'cancelled']);
        Schema::table('event_attendees', function (Blueprint $table): void {
            $table->string('ticket_token', 64)->nullable()->change();
        });
        if (! Schema::hasTable('event_payment_records')) {
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
        }
        if (! Schema::hasTable('event_cancellation_requests')) {
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
            });
        }
        if (! Schema::hasIndex('event_cancellation_requests', ['event_registration_id', 'submission_key'], 'unique')) {
            Schema::table('event_cancellation_requests', function (Blueprint $table): void {
                $table->unique(['event_registration_id', 'submission_key'], 'event_cancellation_submission_unique');
            });
        }
        if (! Schema::hasTable('event_refund_records')) {
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
            });
        }
        if (! Schema::hasIndex('event_refund_records', ['event_payment_record_id', 'submission_key'], 'unique')) {
            Schema::table('event_refund_records', function (Blueprint $table): void {
                $table->unique(['event_payment_record_id', 'submission_key'], 'event_refund_submission_unique');
            });
        }
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
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['status', 'price_centavos', 'total_centavos', 'payment_instructions', 'expires_at', 'expired_at']);
        });
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('payment_instructions');
        });
    }
};
