<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cek apakah tanggal & jam yang diajukan sudah terisi agenda lain.
 * Hanya peringatan: pengajuan tetap boleh dikirim (keputusan akhir di Prokopim).
 */
class ScheduleCheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'start' => ['nullable', 'date_format:H:i'],
            'end' => ['nullable', 'date_format:H:i'],
            'time_unknown' => ['nullable', 'boolean'],
            'leader' => ['nullable', 'string', 'max:50'],
            'exclude' => ['nullable', 'integer'],
        ]);

        $timeKnown = ! $request->boolean('time_unknown') && ! empty($validated['start']) && ! empty($validated['end']);
        $leader = $validated['leader'] ?? null;

        $agendas = Agenda::query()
            ->whereDate('event_date', $validated['date'])
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->when(! empty($validated['exclude']), fn ($q) => $q->where('id', '!=', $validated['exclude']))
            ->when($leader, fn ($q) => $q->where('leader_target', $leader))
            ->when($timeKnown, function ($q) use ($validated): void {
                $q->where(function ($overlap) use ($validated): void {
                    $overlap->where('time_unknown', true)
                        ->orWhere(fn ($w) => $w->where('start_time', '<', $validated['end'])->where('end_time', '>', $validated['start']));
                });
            })
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $showTitles = (bool) $request->user();
        $dispositions = config('agpim.dispositions');

        return response()->json([
            'leader_filtered' => (bool) $leader,
            'time_known' => $timeKnown,
            'items' => $agendas->map(fn (Agenda $agenda) => [
                'time' => $agenda->time_unknown ? 'Jam belum ditentukan' : $agenda->time_range_display,
                'leader' => $dispositions[$agenda->leader_target] ?? 'Pejabat belum ditentukan',
                'title' => $showTitles ? $agenda->title : null,
            ])->values(),
        ]);
    }
}
