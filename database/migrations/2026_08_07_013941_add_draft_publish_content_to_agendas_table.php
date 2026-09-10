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
            $table->longText('visual_content_draft')->nullable()->after('visual_content');
            $table->longText('visual_content_published')->nullable()->after('visual_content_draft');
            $table->longText('video_content_draft')->nullable()->after('video_content');
            $table->longText('video_content_published')->nullable()->after('video_content_draft');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['visual_content_draft', 'visual_content_published', 'video_content_draft', 'video_content_published']);
        });
    }
};
