<?php

namespace App\Events;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgendaSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Agenda $agenda,
        public ?User $actor = null,
    ) {}
}
