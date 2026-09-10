<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaBlast extends Model
{
    protected $fillable = [
        'title',
        'message',
        'backend',
        'target',
        'target_role',
        'status',
        'total_recipients',
        'success_count',
        'failed_count',
        'created_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'total_recipients' => 'integer',
            'success_count' => 'integer',
            'failed_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WaBlastRecipient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
