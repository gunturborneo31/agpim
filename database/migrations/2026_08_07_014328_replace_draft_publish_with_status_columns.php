<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Drop old draft/publish columns
            $table->dropColumn(['visual_content_draft', 'visual_content_published', 'video_content_draft', 'video_content_published']);
            
            // Add new status columns
            $table->enum('visual_content_status', ['draft', 'fix'])->nullable()->default(null);
            $table->enum('video_content_status', ['draft', 'fix'])->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Drop status columns
            $table->dropColumn(['visual_content_status', 'video_content_status']);
            
            // Re-add old columns
            $table->longText('visual_content_draft')->nullable();
            $table->longText('visual_content_published')->nullable();
            $table->longText('video_content_draft')->nullable();
            $table->longText('video_content_published')->nullable();
        });
    }
};
