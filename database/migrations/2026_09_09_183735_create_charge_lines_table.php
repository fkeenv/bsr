<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fee_type_name');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index('charge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_lines');
    }
};
