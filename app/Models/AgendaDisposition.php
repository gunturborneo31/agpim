<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaDisposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_id',
        'proposed_by',
        'approved_by',
        'action',
        'official_name',
        'official_title',
        'note',
        'status',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
