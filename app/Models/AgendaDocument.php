<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_id',
        'uploaded_by',
        'category',
        'title',
        'source_from',
        'description',
        'file_path',
        'mime_type',
        'is_public_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_public_internal' => 'boolean',
        ];
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
