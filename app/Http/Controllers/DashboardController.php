<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $leader = $request->string('leader')->toString() ?: 'bupati';
        $today = Carbon::today();

        $agendas = Agenda::query()
            ->with(['opd', 'type'])
            ->forLeader($leader)
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get();

        $todayAgendas = $agendas->filter(fn (Agenda $agenda) => $agenda->event_date->isSameDay($today));
        $nextAgenda = $agendas->first();

        return view('dashboards.leader', [
            'leader' => $leader,
            'leaders' => [
                'bupati' => 'Bupati',
                'wakil_bupati' => 'Wakil Bupati',
                'sekda' => 'Sekretaris Daerah',
            ],
            'summary' => [
                'today' => $todayAgendas->count(),
                'highPriority' => $todayAgendas->where('priority', 'tinggi')->count(),
                'pendingDecision' => $todayAgendas->where('status', 'awaiting_disposition')->count(),
                'completed' => $todayAgendas->where('status', 'completed')->count(),
            ],
            'nextAgenda' => $nextAgenda,
            'timeline' => $todayAgendas,
            'pending' => $agendas->where('status', 'awaiting_disposition')->take(4),
            'priorities' => $agendas->where('priority', 'tinggi')->take(4),
            'quickTabs' => $this->buildQuickTabs($agendas, $today),
            'conflicts' => $this->detectConflicts($todayAgendas),
        ]);
    }

    private function buildQuickTabs(Collection $agendas, Carbon $today): array
    {
        return [
            'Hari Ini' => $agendas->filter(fn (Agenda $agenda) => $agenda->event_date->isSameDay($today)),
            'Besok' => $agendas->filter(fn (Agenda $agenda) => $agenda->event_date->isSameDay($today->copy()->addDay())),
            'Lusa' => $agendas->filter(fn (Agenda $agenda) => $agenda->event_date->isSameDay($today->copy()->addDays(2))),
            'Minggu Ini' => $agendas->filter(fn (Agenda $agenda) => $agenda->event_date->betweenIncluded($today, $today->copy()->endOfWeek())),
        ];
    }

    private function detectConflicts(Collection $agendas): Collection
    {
        return $agendas->values()->flatMap(function (Agenda $agenda, int $index) use ($agendas) {
            return $agendas->slice($index + 1)->filter(function (Agenda $candidate) use ($agenda) {
                return $agenda->start_time < $candidate->end_time && $agenda->end_time > $candidate->start_time;
            })->map(fn (Agenda $candidate) => [
                'a' => $agenda,
                'b' => $candidate,
                'window' => $agenda->start_time.' - '.$candidate->end_time,
                'recommendation' => 'Pertimbangkan disposisi ke pejabat lain atau ubah slot agenda.',
            ]);
        })->take(3);
    }
}
