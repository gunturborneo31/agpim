<?php

namespace App\Http\Controllers;

use App\Models\AgendaRevisionLink;
use App\Models\AgendaType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Halaman revisi publik yang dibuka dari link WhatsApp (tanpa login).
 * Link hanya bisa dipakai sekali; setelah revisi dikirim, pengajuan kembali berstatus "Diajukan".
 */
class AgendaRevisionController extends Controller
{
    public function edit(string $token): View
    {
        $link = AgendaRevisionLink::findByToken($token);
        $done = session('revision_done') === true;

        $reason = match (true) {
            $done => null,
            $link === null => 'Link revisi tidak dikenali. Pastikan Anda membuka link yang sama persis dengan yang dikirim Prokopim.',
            default => $link->unusableReason(),
        };

        return view('revisions.edit', [
            'token' => $token,
            'link' => $link,
            'agenda' => $link?->agenda?->load(['opd', 'type']),
            'reason' => $reason,
            'done' => $done,
            'agendaTypes' => AgendaType::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $link = AgendaRevisionLink::findByToken($token);
        if (! $link || $link->unusableReason()) {
            return redirect()->route('revisions.edit', $token);
        }

        $dispositionTargets = array_keys(array_filter(
            config('agpim.dispositions'),
            static fn (string $key): bool => $key !== 'rejected',
            ARRAY_FILTER_USE_KEY,
        ));

        $validated = $request->validate([
            'agenda_type_id' => ['required', 'exists:agenda_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'time_unknown' => ['nullable', 'boolean'],
            'start_time' => [Rule::requiredIf(fn () => ! $request->boolean('time_unknown')), 'nullable', 'date_format:H:i'],
            'end_time' => [
                Rule::requiredIf(fn () => ! $request->boolean('time_unknown')),
                'nullable',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (! $request->boolean('time_unknown') && $request->input('start_time') && strcmp((string) $value, (string) $request->input('start_time')) <= 0) {
                        $fail('Jam selesai harus lebih besar dari jam mulai.');
                    }
                },
            ],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'leader_target' => ['required', Rule::in($dispositionTargets)],
            'person_in_charge' => ['nullable', 'string', 'max:255'],
            'pic_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s]*$/'],
            'invitation_letter_path' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'speech_draft_path' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'speech_draft_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $timeUnknown = $request->boolean('time_unknown');
        $attributes = [
            'agenda_type_id' => $validated['agenda_type_id'],
            'title' => $validated['title'],
            'event_date' => $validated['event_date'],
            'time_unknown' => $timeUnknown,
            'start_time' => $timeUnknown ? '00:00' : $validated['start_time'],
            'end_time' => $timeUnknown ? '00:00' : $validated['end_time'],
            'location' => $validated['location'],
            'description' => $validated['description'],
            'leader_target' => $validated['leader_target'],
            'person_in_charge' => $validated['person_in_charge'] ?? null,
            'pic_phone' => $validated['pic_phone'] ?? null,
            'speech_draft_note' => $validated['speech_draft_note'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ];

        if ($request->hasFile('invitation_letter_path')) {
            $attributes['invitation_letter_path'] = $request->file('invitation_letter_path')->store('agendas/invitations', 'public');
        }
        if ($request->hasFile('speech_draft_path')) {
            $attributes['speech_draft_path'] = $request->file('speech_draft_path')->store('agendas/speeches', 'public');
        }

        $applied = DB::transaction(function () use ($link, $attributes): bool {
            // Kunci baris link supaya dua kiriman bersamaan tidak sama-sama lolos.
            $fresh = AgendaRevisionLink::query()->lockForUpdate()->find($link->id);
            if (! $fresh || $fresh->unusableReason()) {
                return false;
            }

            $agenda = $fresh->agenda;
            $agenda->update($attributes);
            $fresh->update(['used_at' => now()]);

            $agenda->verifications()->create([
                'verified_by' => $agenda->submitted_by,
                'action' => 'resubmitted',
                'note' => 'Pengajuan direvisi lewat link WhatsApp dan diajukan ulang.',
                'metadata' => ['source' => 'revision_link', 'revision_link_id' => $fresh->id],
            ]);

            return true;
        });

        return $applied
            ? redirect()->route('revisions.edit', $token)->with('revision_done', true)
            : redirect()->route('revisions.edit', $token);
    }
}
