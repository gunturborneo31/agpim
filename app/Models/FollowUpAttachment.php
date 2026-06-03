<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['agenda_follow_up_id', 'title', 'description', 'file_path', 'media_type'];

    public function followUp(): BelongsTo
    {
        return $this->belongsTo(AgendaFollowUp::class, 'agenda_follow_up_id');
    }
}
