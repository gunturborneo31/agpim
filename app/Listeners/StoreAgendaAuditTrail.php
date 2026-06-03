<?php

namespace App\Listeners;

use App\Events\AgendaSubmitted;
use App\Models\AuditLog;

class StoreAgendaAuditTrail
{
    public function handle(AgendaSubmitted $event): void
    {
        AuditLog::create([
            'user_id' => $event->actor?->id,
            'auditable_type' => $event->agenda::class,
            'auditable_id' => $event->agenda->id,
            'action' => 'agenda.submitted',
            'after_state' => $event->agenda->fresh()?->toArray(),
            'metadata' => [
                'status' => $event->agenda->status,
                'leader_target' => $event->agenda->leader_target,
            ],
        ]);
    }
}
