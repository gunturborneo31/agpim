<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\View\View;

class PublicAgendaController extends Controller
{
    public function __invoke(): View
    {
        return view('public-agendas.index', [
            'agendas' => Agenda::query()
                ->with(['opd', 'type'])
                ->where('is_internal_public', true)
                ->orderBy('event_date')
                ->orderBy('start_time')
                ->get(),
        ]);
    }
}
