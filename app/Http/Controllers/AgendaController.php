<?php

namespace App\Http\Controllers;

use App\Events\AgendaSubmitted;
use App\Http\Requests\StoreAgendaRequest;
use App\Models\Agenda;
use App\Models\AgendaType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function index(): View
    {
        $agendas = Agenda::query()
            ->with(['opd', 'type', 'submitter'])
            ->latest('event_date')
            ->latest('start_time')
            ->get();

        return view('agendas.index', [
            'agendas' => $agendas,
            'agendaTypes' => AgendaType::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAgendaRequest $request): RedirectResponse
    {
        $agenda = DB::transaction(function () use ($request) {
            $agenda = Agenda::create([
                ...$request->validated(),
                'opd_id' => $request->user()->opd_id,
                'submitted_by' => $request->user()->id,
                'status' => 'submitted',
                'submitted_at' => now(),
                'is_internal_public' => false,
            ]);

            $agenda->verifications()->create([
                'verified_by' => $request->user()->id,
                'action' => 'submitted',
                'note' => 'Pengajuan dibuat oleh OPD.',
                'metadata' => ['source' => 'web'],
            ]);

            return $agenda;
        });

        AgendaSubmitted::dispatch($agenda->load('opd'), $request->user());

        return to_route('agendas.index')->with('status', 'Agenda berhasil diajukan.');
    }

    public function show(Agenda $agenda): View
    {
        if (! $agenda->is_internal_public && request()->user()) {
            $this->authorize('view', $agenda);
        }

        abort_unless($agenda->is_internal_public || request()->user(), 403);

        return view('agendas.show', [
            'agenda' => $agenda->load(['opd', 'type', 'documents', 'dispositions', 'followUps.attachments']),
            'conflicts' => $agenda->conflicts(),
        ]);
    }
}
