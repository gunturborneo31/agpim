<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublicAgendaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $now = Carbon::now();
        $month = (int) $request->integer('month', $now->month);
        $year = (int) $request->integer('year', $now->year);
        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        $selectedStatus = $request->string('status')->toString();
        if (! in_array($selectedStatus, ['all', 'submitted', 'rejected', 'approved', 'awaiting_disposition'], true)) {
            $selectedStatus = 'submitted';
        }

        $selectedOpdId = (int) $request->integer('opd_id', 0);
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $resumeRows = Agenda::query()
            ->join('opds', 'opds.id', '=', 'agendas.opd_id')
            ->whereBetween('agendas.event_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->groupBy('agendas.opd_id', 'opds.name')
            ->orderBy('opds.name')
            ->selectRaw('agendas.opd_id as opd_id, opds.name as opd_name')
            ->selectRaw('COUNT(*) as all_count')
            ->selectRaw("SUM(CASE WHEN agendas.status = 'submitted' THEN 1 ELSE 0 END) as submitted_count")
            ->selectRaw("SUM(CASE WHEN agendas.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count")
            ->selectRaw("SUM(CASE WHEN agendas.status = 'approved' THEN 1 ELSE 0 END) as approved_count")
            ->selectRaw("SUM(CASE WHEN agendas.status = 'awaiting_disposition' THEN 1 ELSE 0 END) as pending_count")
            ->get()
            ->map(fn ($row) => [
                'opd_id' => (int) $row->opd_id,
                'opd_name' => $row->opd_name,
                'all' => (int) $row->all_count,
                'submitted' => (int) $row->submitted_count,
                'rejected' => (int) $row->rejected_count,
                'approved' => (int) $row->approved_count,
                'pending' => (int) $row->pending_count,
            ]);

        $totals = [
            'all' => Agenda::query()
                ->whereBetween('event_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->count(),
            'submitted' => $resumeRows->sum('submitted'),
            'approved' => $resumeRows->sum('approved'),
            'pending' => $resumeRows->sum('pending'),
            'rejected' => $resumeRows->sum('rejected'),
        ];

        if ($selectedOpdId <= 0 && $resumeRows->isNotEmpty()) {
            $selectedOpdId = (int) $resumeRows->first()['opd_id'];
        }

        $selectedOpd = $selectedOpdId > 0 ? Opd::query()->find($selectedOpdId) : null;
        $agendaList = $this->buildAgendaList($selectedOpdId, $selectedStatus, $periodStart, $periodEnd);

        $yearOptions = range($now->year - 3, $now->year + 1);

        return view('public-agendas.index', [
            'month' => $month,
            'year' => $year,
            'monthLabel' => $periodStart->translatedFormat('F Y'),
            'yearOptions' => $yearOptions,
            'resumeRows' => $resumeRows,
            'totals' => $totals,
            'selectedStatus' => $selectedStatus,
            'selectedOpdId' => $selectedOpdId,
            'selectedOpdName' => $selectedOpd?->name,
            'agendaList' => $agendaList,
        ]);
    }

    private function buildAgendaList(int $opdId, string $status, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        if ($opdId <= 0) {
            return collect();
        }

        $query = Agenda::query()
            ->with(['opd', 'type', 'documents'])
            ->where('opd_id', $opdId)
            ->whereBetween('event_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderByDesc('event_date')
            ->orderByDesc('start_time');

        if ($status === 'submitted') {
            $query->where('status', 'submitted');
        }

        if ($status === 'rejected') {
            $query->where('status', 'rejected');
        }

        if ($status === 'approved') {
            $query->where('status', 'approved');
        }

        if ($status === 'awaiting_disposition') {
            $query->where('status', 'awaiting_disposition');
        }

        return $query->get();
    }
}
