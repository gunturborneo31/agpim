<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category')->default('supporting');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->boolean('is_public_internal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_documents');
    }
};
