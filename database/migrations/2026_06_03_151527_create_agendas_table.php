<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('opd_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location');
            $table->text('description');
            $table->string('priority')->default('biasa')->index();
            $table->string('status')->default('draft')->index();
            $table->string('leader_target')->nullable()->index();
            $table->string('person_in_charge')->nullable();
            $table->string('pic_phone', 30)->nullable();
            $table->string('invitation_letter_path');
            $table->string('speech_draft_path')->nullable();
            $table->string('delegate_name')->nullable();
            $table->string('delegate_title')->nullable();
            $table->text('verification_note')->nullable();
            $table->text('disposition_note')->nullable();
            $table->boolean('is_internal_public')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
