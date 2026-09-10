<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AgendaType;
use App\Models\Opd;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        $agendaTypes = cache()->remember('landing.agenda_types', now()->addMinutes(10), function () {
            return AgendaType::query()
                ->orderBy('name')
                ->get(['id', 'name']);
        });

        $stats = cache()->remember('landing.stats', now()->addMinutes(5), function () use ($agendaTypes) {
            $agendaStats = Agenda::query()
                ->selectRaw("COUNT(*) as agendaCount, SUM(CASE WHEN priority = 'tinggi' THEN 1 ELSE 0 END) as highPriorityCount")
                ->first();

            return [
                'agendaCount' => (int) $agendaStats->agendaCount,
                'highPriorityCount' => (int) $agendaStats->highPriorityCount,
                'opdCount' => Opd::count(),
                'typeCount' => $agendaTypes->count(),
            ];
        });

        return view('home', [
            'stats' => $stats,
            'agendaTypes' => $agendaTypes,
            'workflow' => config('agpim.statuses'),
        ]);
    }
}
