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
        return view('home', [
            'stats' => [
                'agendaCount' => Agenda::count(),
                'highPriorityCount' => Agenda::where('priority', 'tinggi')->count(),
                'opdCount' => Opd::count(),
                'typeCount' => AgendaType::count(),
            ],
            'agendaTypes' => AgendaType::query()->orderBy('name')->get(),
            'workflow' => config('agpim.statuses'),
        ]);
    }
}
