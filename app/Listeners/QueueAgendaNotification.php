<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\AgendaSubmitted;
use App\Models\AppNotification;
use App\Models\User;

class QueueAgendaNotification
{
    public function handle(AgendaSubmitted $event): void
    {
        $recipients = User::query()
            ->whereIn('role', [UserRole::AdminProkopim->value, UserRole::SuperAdmin->value])
            ->get();

        foreach ($recipients as $recipient) {
            AppNotification::create([
                'user_id' => $recipient->id,
                'type' => 'agenda_submitted',
                'title' => 'Pengajuan agenda baru',
                'body' => sprintf('%s mengajukan agenda %s.', $event->agenda->opd->name, $event->agenda->title),
                'data' => [
                    'agenda_id' => $event->agenda->id,
                    'status' => $event->agenda->status,
                ],
                'channels' => ['in_app', 'email'],
                'scheduled_for' => now(),
            ]);
        }
    }
}
