<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->longText('draft_berita')->nullable()->after('speech_draft_path');
            $table->longText('draft_konten')->nullable()->after('draft_berita');
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['draft_berita', 'draft_konten']);
        });
    }
};
