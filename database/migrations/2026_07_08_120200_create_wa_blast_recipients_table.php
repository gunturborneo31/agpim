<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_blast_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wa_blast_id')->constrained('wa_blasts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name');
            $table->string('raw_phone', 40);
            $table->string('e164_phone', 30)->nullable()->index();
            $table->string('web_jid', 40)->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['wa_blast_id', 'raw_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_blast_recipients');
    }
};
