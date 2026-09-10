<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_documents', function (Blueprint $table) {
            $table->string('source_from')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_documents', function (Blueprint $table) {
            $table->dropColumn('source_from');
        });
    }
};
