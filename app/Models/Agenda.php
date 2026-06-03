<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Agenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'opd_id',
        'agenda_type_id',
        'submitted_by',
        'reviewed_by',
        'title',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'description',
        'priority',
        'status',
        'leader_target',
        'person_in_charge',
        'pic_phone',
        'invitation_letter_path',
        'speech_draft_path',
        'delegate_name',
        'delegate_title',
        'verification_note',
        'disposition_note',
        'is_internal_public',
        'submitted_at',
        'verified_at',
        'decided_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'decided_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_internal_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $agenda): void {
            $agenda->uuid ??= (string) Str::uuid();
        });
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AgendaType::class, 'agenda_type_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AgendaDocument::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(AgendaVerification::class);
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(AgendaDisposition::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(AgendaFollowUp::class);
    }

    public function scopeForLeader($query, string $leader)
    {
        return $query->where('leader_target', $leader);
    }

    public function conflicts(): Collection
    {
        return self::query()
            ->whereKeyNot($this->getKey())
            ->whereDate('event_date', $this->event_date)
            ->where('leader_target', $this->leader_target)
            ->where(function ($query): void {
                $query
                    ->whereBetween('start_time', [$this->start_time, $this->end_time])
                    ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                    ->orWhere(function ($nested): void {
                        $nested
                            ->where('start_time', '<=', $this->start_time)
                            ->where('end_time', '>=', $this->end_time);
                    });
            })
            ->get();
    }
}
