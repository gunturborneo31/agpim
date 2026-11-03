<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur Konten & Dokumentasi (konten visual/video, berita, bahan, hasil editing,
 * draft berita/konten) dipindah ke aplikasi terpisah. Lihat PRD "Aplikasi Konten &
 * Dokumentasi Agenda Pimpinan". Data lama dicadangkan di
 * storage/app/exports/konten-agenda-backup-2026-10-31.json sebelum migration ini dijalankan.
 */
return new class extends Migration
{
    private array $contentColumns = [
        'visual_content',
        'visual_other_note',
        'visual_status_ak',
        'visual_status_prokopim',
        'visual_content_status',
        'visual_published_link',
        'video_content',
        'video_status_ak',
        'video_status_prokopim',
        'video_content_status',
        'video_published_link',
        'draft_berita',
        'draft_konten',
    ];

    public function up(): void
    {
        Schema::dropIfExists('agenda_documents');

        $existing = array_values(array_filter(
            $this->contentColumns,
            fn (string $column) => Schema::hasColumn('agendas', $column),
        ));

        if ($existing !== []) {
            Schema::table('agendas', function (Blueprint $table) use ($existing): void {
                $table->dropColumn($existing);
            });
        }
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table): void {
            $table->json('visual_content')->nullable();
            $table->text('visual_other_note')->nullable();
            $table->string('visual_status_ak')->nullable();
            $table->string('visual_status_prokopim')->nullable();
            $table->enum('visual_content_status', ['draft', 'fix'])->nullable();
            $table->string('visual_published_link')->nullable();
            $table->text('video_content')->nullable();
            $table->string('video_status_ak')->nullable();
            $table->string('video_status_prokopim')->nullable();
            $table->enum('video_content_status', ['draft', 'fix'])->nullable();
            $table->string('video_published_link')->nullable();
            $table->longText('draft_berita')->nullable();
            $table->longText('draft_konten')->nullable();
        });

        Schema::create('agenda_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agenda_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category')->default('supporting');
            $table->string('title');
            $table->string('source_from')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->boolean('is_public_internal')->default(false);
            $table->timestamps();
        });
    }
};
