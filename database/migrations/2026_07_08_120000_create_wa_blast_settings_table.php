<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_blast_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('cloud_enabled')->default(false);
            $table->boolean('web_enabled')->default(false);
            $table->string('default_backend', 20)->default('auto');
            $table->string('web_session_name', 80)->default('main');
            $table->unsignedSmallInteger('send_delay_seconds')->default(2);
            $table->string('message_footer', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_blast_settings');
    }
};
