<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaBlastRecipient extends Model
{
    protected $fillable = [
        'wa_blast_id',
        'user_id',
        'recipient_name',
        'raw_phone',
        'e164_phone',
        'web_jid',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function blast(): BelongsTo
    {
        return $this->belongsTo(WaBlast::class, 'wa_blast_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
