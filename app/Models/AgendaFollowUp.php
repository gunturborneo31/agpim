<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaFollowUp extends Model
{
    use HasFactory;

    protected $fillable = ['agenda_id', 'submitted_by', 'summary', 'next_steps', 'additional_notes', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FollowUpAttachment::class);
    }
}
