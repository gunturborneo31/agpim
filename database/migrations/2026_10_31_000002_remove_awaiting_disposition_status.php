<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status "Menunggu Disposisi" dihapus: disposisi sudah diisi di form verifikasi,
 * jadi agenda yang disetujui langsung berstatus "Disetujui".
 * Tabel agenda_dispositions tidak pernah dipakai dan ikut dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('agendas')
            ->where('status', 'awaiting_disposition')
            ->update([
                'status' => 'approved',
                'decided_at' => DB::raw('COALESCE(decided_at, verified_at, updated_at)'),
            ]);

        Schema::dropIfExists('agenda_dispositions');
    }

    public function down(): void
    {
        // Status lama tidak dikembalikan: tidak bisa dibedakan dari agenda yang memang disetujui.
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
};
