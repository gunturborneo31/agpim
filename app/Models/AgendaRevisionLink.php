<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Link revisi sekali pakai untuk pengajuan berstatus "Perlu revisi".
 */
class AgendaRevisionLink extends Model
{
    public const VALID_DAYS = 14;

    protected $fillable = ['agenda_id', 'created_by', 'token_hash', 'expires_at', 'used_at', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    /**
     * Buat link baru dan cabut link lama yang belum dipakai. Mengembalikan token asli
     * (hanya ada di sini; database menyimpan hash-nya saja).
     */
    public static function issueFor(Agenda $agenda, ?User $creator = null): string
    {
        static::query()
            ->where('agenda_id', $agenda->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $token = Str::random(48);

        static::query()->create([
            'agenda_id' => $agenda->id,
            'created_by' => $creator?->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(self::VALID_DAYS),
        ]);

        return $token;
    }

    public static function findByToken(string $token): ?self
    {
        return static::query()->where('token_hash', hash('sha256', $token))->first();
    }

    /** Alasan link tidak bisa dipakai, atau null bila masih berlaku. */
    public function unusableReason(): ?string
    {
        return match (true) {
            $this->used_at !== null => 'Revisi untuk link ini sudah dikirim. Link tidak dapat digunakan lagi.',
            $this->revoked_at !== null => 'Link ini sudah diganti dengan link yang lebih baru. Gunakan link terakhir yang dikirim Prokopim.',
            $this->expires_at->isPast() => 'Link revisi sudah kedaluwarsa. Hubungi Prokopim untuk meminta link baru.',
            $this->agenda?->status !== 'needs_revision' => 'Pengajuan ini tidak lagi berstatus perlu revisi.',
            default => null,
        };
    }
}
