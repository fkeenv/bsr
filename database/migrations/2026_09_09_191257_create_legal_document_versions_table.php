<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_document_versions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->text('body');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(['type', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_versions');
    }
};
