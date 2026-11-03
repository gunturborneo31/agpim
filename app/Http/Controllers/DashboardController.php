<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Agenda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $leaders = [
            'bupati' => 'Bupati',
            'wakil_bupati' => 'Wakil Bupati',
            'sekda' => 'Sekretaris Daerah',
        ];

        $leader = $request->string('leader')->toString() ?: 'bupati';
        $userRole = $request->user()?->role;
        if ($userRole instanceof UserRole) {
            if ($userRole === UserRole::Bupati) {
                $leader = 'bupati';
            } elseif ($userRole === UserRole::WakilBupati) {
                $leader = 'wakil_bupati';
            } elseif ($userRole === UserRole::Sekda) {
                $leader = 'sekda';
            }
        }

        if (! array_key_exists($leader, $leaders)) {
            $leader = 'bupati';
        }

        $today = Carbon::today();
        $calendarMonth = Carbon::createFromDate(
            (int) $request->integer('calendar_year', $today->year),
            (int) $request->integer('calendar_month', $today->month),
            1,
        )->startOfMonth();
        $calendarStart = $calendarMonth->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $agendas = Agenda::query()
            ->with(['opd', 'type'])
            ->forLeader($leader)
            ->where('status', '!=', 'cancelled')
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get();

        $calendarAgendas = Agenda::query()
            ->with(['opd', 'type'])
            ->forLeader($leader)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('event_date', [$calendarMonth->copy()->startOfMonth()->toDateString(), $calendarMonth->copy()->endOfMonth()->toDateString()])
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get();

        $todayAgendas = $agendas->filter(fn (Agenda $agenda) => Carbon::parse($agenda->event_date)->isSameDay($today));
        // Agenda berikutnya = yang belum selesai (agenda tanpa jam pasti tetap dihitung).
        $nextAgenda = $agendas->first(function (Agenda $agenda) {
            if ($agenda->time_unknown || ! $agenda->end_time) {
                return true;
            }

            return Carbon::parse($agenda->event_date)->setTimeFromTimeString($agenda->end_time)->isFuture();
        });
        $calendarAgendaMap = $calendarAgendas->groupBy(fn (Agenda $agenda) => Carbon::parse($agenda->event_date)->toDateString());

        $calendarDays = collect();
        for ($date = $calendarStart->copy(); $date->lte($calendarEnd); $date->addDay()) {
            $dateKey = $date->toDateString();

            $calendarDays->push([
                'date' => $dateKey,
                'day' => $date->day,
                'month' => $date->month,
                'isCurrentMonth' => $date->isSameMonth($calendarMonth),
                'isToday' => $date->isSameDay($today),
                'count' => $calendarAgendaMap->get($dateKey, collect())->count(),
            ]);
        }

        $calendarMap = $calendarAgendaMap->map(fn ($items) => $items->map(fn (Agenda $agenda) => [
            'title' => $agenda->title,
            'date' => Carbon::parse($agenda->event_date)->translatedFormat('d M Y'),
            'time' => $agenda->time_range_display,
            'location' => $agenda->location,
            'type' => $agenda->type->name,
            'priority' => $agenda->priority,
            'priorityLabel' => config('agpim.priorities')[$agenda->priority]['label'],
        ]));

        // Ganti bulan kalender lewat AJAX: kirim kalender saja, tanpa seluruh halaman.
        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('dashboards.partials.calendar', [
                    'leader' => $leader,
                    'calendarMonth' => $calendarMonth,
                    'calendarMonthLabel' => $calendarMonth->translatedFormat('F Y'),
                    'calendarDays' => $calendarDays,
                ])->render(),
                'map' => $calendarMap,
                'year' => $calendarMonth->year,
                'month' => $calendarMonth->month,
                'label' => $calendarMonth->translatedFormat('F Y'),
            ])->header('Vary', 'Accept');
        }

        return view('dashboards.leader', [
            'leader' => $leader,
            'leaders' => $leaders,
            'leaderLabel' => $leaders[$leader],
            'calendarMonth' => $calendarMonth,
            'calendarMonthLabel' => $calendarMonth->translatedFormat('F Y'),
            'summary' => [
                'today' => $todayAgendas->count(),
                'highPriority' => $todayAgendas->where('priority', 'tinggi')->count(),
                'completed' => $todayAgendas->where('status', 'completed')->count(),
            ],
            'nextAgenda' => $nextAgenda,
            'timeline' => $todayAgendas,
            'calendarDays' => $calendarDays,
            'calendarAgendaMap' => $calendarMap,
            'priorities' => $agendas->where('priority', 'tinggi')->take(4),
            'quickTabs' => $this->buildQuickTabs($agendas, $today),
            'conflicts' => $this->detectConflicts($todayAgendas),
        ]);
    }

    private function buildQuickTabs(Collection $agendas, Carbon $today): array
    {
        return [
            'Hari Ini' => $agendas->filter(fn (Agenda $agenda) => Carbon::parse($agenda->event_date)->isSameDay($today)),
            'Besok' => $agendas->filter(fn (Agenda $agenda) => Carbon::parse($agenda->event_date)->isSameDay($today->copy()->addDay())),
            'Lusa' => $agendas->filter(fn (Agenda $agenda) => Carbon::parse($agenda->event_date)->isSameDay($today->copy()->addDays(2))),
            'Minggu Ini' => $agendas->filter(fn (Agenda $agenda) => Carbon::parse($agenda->event_date)->betweenIncluded($today, $today->copy()->endOfWeek())),
        ];
    }

    private function detectConflicts(Collection $agendas): Collection
    {
        $agendas = $agendas->reject(fn (Agenda $agenda) => $agenda->time_unknown);

        return $agendas->values()->flatMap(function (Agenda $agenda, int $index) use ($agendas) {
            return $agendas->slice($index + 1)->filter(function (Agenda $candidate) use ($agenda) {
                return $agenda->start_time < $candidate->end_time && $agenda->end_time > $candidate->start_time;
            })->map(fn (Agenda $candidate) => [
                'a' => $agenda,
                'b' => $candidate,
                'window' => $agenda->start_time_display.' - '.$candidate->end_time_display,
                'recommendation' => 'Pertimbangkan disposisi ke pejabat lain atau ubah slot agenda.',
            ]);
        })->take(3);
    }
}
