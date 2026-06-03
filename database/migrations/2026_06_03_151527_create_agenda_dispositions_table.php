<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('official_name')->nullable();
            $table->string('official_title')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_dispositions');
    }
};
