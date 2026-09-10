<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->json('visual_content')->nullable()->after('description');
            $table->text('visual_other_note')->nullable()->after('visual_content');
            $table->string('visual_status_ak')->nullable()->after('visual_other_note');
            $table->string('visual_status_prokopim')->nullable()->after('visual_status_ak');
            $table->text('video_content')->nullable()->after('visual_status_prokopim');
            $table->string('video_status_ak')->nullable()->after('video_content');
            $table->string('video_status_prokopim')->nullable()->after('video_status_ak');
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn([
                'visual_content',
                'visual_other_note',
                'visual_status_ak',
                'visual_status_prokopim',
                'video_content',
                'video_status_ak',
                'video_status_prokopim',
            ]);
        });
    }
};
