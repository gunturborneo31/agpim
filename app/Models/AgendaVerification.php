<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaVerification extends Model
{
    use HasFactory;

    protected $fillable = ['agenda_id', 'verified_by', 'action', 'note', 'metadata'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
