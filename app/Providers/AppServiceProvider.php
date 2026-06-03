<?php

namespace App\Providers;

use App\Events\AgendaSubmitted;
use App\Listeners\QueueAgendaNotification;
use App\Listeners\StoreAgendaAuditTrail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(AgendaSubmitted::class, StoreAgendaAuditTrail::class);
        Event::listen(AgendaSubmitted::class, QueueAgendaNotification::class);
    }
}
