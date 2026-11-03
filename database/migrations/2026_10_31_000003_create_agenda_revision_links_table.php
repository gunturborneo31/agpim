<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link revisi sekali pakai yang dikirim ke pengaju lewat WhatsApp saat pengajuan
 * berstatus "Perlu revisi". Token disimpan sebagai hash SHA-256.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_revision_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_revision_links');
    }
};
