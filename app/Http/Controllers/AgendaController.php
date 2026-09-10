<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Events\AgendaSubmitted;
use App\Http\Requests\StoreAgendaRequest;
use App\Http\Requests\UpdateAgendaRequest;
use App\Models\Agenda;
use App\Models\AgendaType;
use App\Models\AppNotification;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isVerificationMode = $user && ! $user->hasRole(UserRole::Opd);
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $opdId = (int) $request->integer('opd_id', 0);
        $startDate = trim((string) $request->string('start_date'));
        $endDate = trim((string) $request->string('end_date'));
        $sort = $request->string('sort')->toString();

        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $sortOptions = [
            'latest' => fn ($query) => $query->latest('event_date')->latest('start_time'),
            'oldest' => fn ($query) => $query->oldest('event_date')->oldest('start_time'),
            'title_asc' => fn ($query) => $query->orderBy('title'),
            'title_desc' => fn ($query) => $query->orderByDesc('title'),
            'opd_asc' => fn ($query) => $query->orderBy(Opd::select('name')->whereColumn('opds.id', 'agendas.opd_id')),
            'opd_desc' => fn ($query) => $query->orderByDesc(Opd::select('name')->whereColumn('opds.id', 'agendas.opd_id')),
            'type_asc' => fn ($query) => $query->orderBy(AgendaType::select('name')->whereColumn('agenda_types.id', 'agendas.agenda_type_id')),
            'type_desc' => fn ($query) => $query->orderByDesc(AgendaType::select('name')->whereColumn('agenda_types.id', 'agendas.agenda_type_id')),
            'priority_high' => fn ($query) => $query->orderByRaw("CASE priority WHEN 'tinggi' THEN 1 WHEN 'sedang' THEN 2 WHEN 'biasa' THEN 3 ELSE 4 END"),
            'priority_low' => fn ($query) => $query->orderByRaw("CASE priority WHEN 'biasa' THEN 1 WHEN 'sedang' THEN 2 WHEN 'tinggi' THEN 3 ELSE 4 END"),
            'status_asc' => fn ($query) => $query->orderByRaw("CASE status WHEN 'draft' THEN 1 WHEN 'submitted' THEN 2 WHEN 'under_review' THEN 3 WHEN 'awaiting_disposition' THEN 4 WHEN 'approved' THEN 5 WHEN 'needs_revision' THEN 6 WHEN 'rejected' THEN 7 WHEN 'completed' THEN 8 ELSE 9 END"),
            'status_desc' => fn ($query) => $query->orderByRaw("CASE status WHEN 'completed' THEN 1 WHEN 'rejected' THEN 2 WHEN 'needs_revision' THEN 3 WHEN 'approved' THEN 4 WHEN 'awaiting_disposition' THEN 5 WHEN 'under_review' THEN 6 WHEN 'submitted' THEN 7 WHEN 'draft' THEN 8 ELSE 9 END"),
        ];

        if (! array_key_exists($sort, $sortOptions)) {
            $sort = 'latest';
        }

        $agendaQuery = Agenda::query()
            ->with(['opd', 'type', 'submitter', 'documents'])
            ->when($user?->hasRole(UserRole::Opd), fn ($query) => $query->where('submitted_by', $user->id))
            ->when($isVerificationMode, function ($query): void {
                $query
                    ->whereHas('submitter', fn ($submitterQuery) => $submitterQuery->where('role', UserRole::Opd->value));
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('opd', fn ($opdQuery) => $opdQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('submitter', fn ($submitterQuery) => $submitterQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($opdId > 0, fn ($query) => $query->where('opd_id', $opdId))
            ->when($startDate !== '', fn ($query) => $query->whereDate('event_date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('event_date', '<=', $endDate))
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority));

        $sortOptions[$sort]($agendaQuery);

        $agendas = $agendaQuery->paginate(10)->withQueryString();

        return view('agendas.index', [
            'agendas' => $agendas,
            'agendaTypes' => AgendaType::query()->orderBy('name')->get(),
            'opdOptions' => Opd::query()->orderBy('name')->get(['id', 'name']),
            'isVerificationMode' => $isVerificationMode,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'priority' => $priority,
                'opd_id' => $opdId > 0 ? (string) $opdId : '',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sort' => $sort,
            ],
        ]);
    }

    public function store(StoreAgendaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['visual_content'] = $request->input('visual_content', []);
        $validated['invitation_letter_path'] = $request->file('invitation_letter_path')->store('agendas/invitations', 'public');

        if ($request->hasFile('speech_draft_path')) {
            $validated['speech_draft_path'] = $request->file('speech_draft_path')->store('agendas/speeches', 'public');
        }

        $timeUnknown = (bool) ($validated['time_unknown'] ?? false);
        $validated['time_unknown'] = $timeUnknown;
        $validated['start_time'] = $timeUnknown ? '00:00' : $validated['start_time'];
        $validated['end_time'] = $timeUnknown ? '00:00' : $validated['end_time'];

        $isAdminDirectApprove = $request->boolean('direct_approve');
        $status = $isAdminDirectApprove ? 'approved' : 'submitted';
        $submitter = $request->user();
        $opdId = $isAdminDirectApprove ? (int) $request->input('opd_id', $submitter->opd_id) : $submitter->opd_id;

        $agenda = DB::transaction(function () use ($validated, $status, $submitter, $opdId): array {
            $agenda = Agenda::create([
                ...$validated,
                'opd_id' => $opdId,
                'submitted_by' => $submitter->id,
                'status' => $status,
                'submitted_at' => now(),
                'verified_at' => $status === 'approved' ? now() : null,
                'decided_at' => $status === 'approved' ? now() : null,
                'is_internal_public' => false,
            ]);

            $agenda->verifications()->create([
                'verified_by' => $submitter->id,
                'action' => $status === 'approved' ? 'approved' : 'submitted',
                'note' => $status === 'approved'
                    ? 'Agenda dibuat langsung oleh admin dan disetujui.'
                    : 'Pengajuan dibuat oleh OPD.',
                'metadata' => ['source' => $status === 'approved' ? 'admin_direct_create' : 'web'],
            ]);

            return [$agenda, $status];
        });

        [$agenda, $status] = $agenda;

        if ($status === 'submitted') {
            AgendaSubmitted::dispatch($agenda->load('opd'), $submitter);
        }

        return to_route('agendas.index')->with('status', $status === 'approved'
            ? 'Agenda berhasil dibuat dan langsung disetujui.'
            : 'Agenda berhasil diajukan.');
    }

    public function show(Agenda $agenda): View
    {
        if (! $agenda->is_internal_public && request()->user()) {
            Gate::authorize('view', $agenda);
        }

        abort_unless($agenda->is_internal_public || request()->user(), 403);

        return view('agendas.show', [
            'agenda' => $agenda->load(['opd', 'type', 'submitter', 'reviewer', 'documents.uploader', 'dispositions', 'followUps.attachments']),
            'conflicts' => $agenda->conflicts(),
        ]);
    }

    public function edit(Request $request, Agenda $agenda): View
    {
        Gate::authorize('update', $agenda);

        if ($agenda->status !== 'submitted') {
            abort(403);
        }

        return view('agendas.edit', [
            'agenda' => $agenda->load(['type']),
            'agendaTypes' => AgendaType::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAgendaRequest $request, Agenda $agenda): RedirectResponse
    {
        if ($agenda->status !== 'submitted') {
            abort(403);
        }

        $validated = $request->validated();
        $validated['visual_content'] = $request->input('visual_content', []);

        $timeUnknown = (bool) ($validated['time_unknown'] ?? false);
        $validated['time_unknown'] = $timeUnknown;
        $validated['start_time'] = $timeUnknown ? '00:00' : $validated['start_time'];
        $validated['end_time'] = $timeUnknown ? '00:00' : $validated['end_time'];

        if ($request->hasFile('invitation_letter_path')) {
            $validated['invitation_letter_path'] = $request->file('invitation_letter_path')->store('agendas/invitations', 'public');
        }

        if ($request->hasFile('speech_draft_path')) {
            $validated['speech_draft_path'] = $request->file('speech_draft_path')->store('agendas/speeches', 'public');
        } else {
            unset($validated['speech_draft_path']);
        }

        DB::transaction(function () use ($request, $agenda, $validated): void {
            $agenda->update($validated);

            $agenda->verifications()->create([
                'verified_by' => $request->user()->id,
                'action' => 'updated_submission',
                'note' => 'Pengajuan diperbarui saat status masih diajukan.',
                'metadata' => ['source' => 'web'],
            ]);
        });

        return to_route('agendas.index')->with('status', 'Pengajuan agenda berhasil diperbarui.');
    }

    public function verify(Request $request, Agenda $agenda): RedirectResponse
    {
        abort_unless($request->user() && ! $request->user()->hasRole(UserRole::Opd), 403);

        if (! $agenda->submitter?->hasRole(UserRole::Opd)) {
            return back()->with('status', 'Agenda ini tidak tersedia untuk verifikasi super admin.');
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'needs_revision', 'rejected'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'attendance_source' => ['nullable', Rule::in(array_keys(config('agpim.attendance_sources')))],
            'leader_target' => ['nullable', Rule::in(array_keys(config('agpim.dispositions')))],
            'delegate_name' => ['nullable', 'string', 'max:255'],
            'delegate_title' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['action'] === 'approve') {
            $request->validate([
                'attendance_source' => ['required', Rule::in(array_keys(config('agpim.attendance_sources')))],
                'leader_target' => ['required', Rule::in(array_keys(config('agpim.dispositions')))],
                'delegate_name' => ['required_if:leader_target,other_official', 'nullable', 'string', 'max:255'],
                'delegate_title' => ['required_if:leader_target,other_official', 'nullable', 'string', 'max:255'],
            ], [
                'attendance_source.required' => 'Pilih keputusan siapa yang akan berangkat.',
                'leader_target.required' => 'Pilih tujuan disposisi agenda.',
                'delegate_name.required_if' => 'Nama pejabat wajib diisi bila disposisi ke pejabat lain.',
                'delegate_title.required_if' => 'Jabatan pejabat wajib diisi bila disposisi ke pejabat lain.',
            ]);

            $proposedLeaderTarget = $agenda->leader_target;

            if (($validated['attendance_source'] ?? null) === 'proposed') {
                if (! $proposedLeaderTarget) {
                    throw ValidationException::withMessages([
                        'leader_target' => 'Target orang yang diajukan OPD belum tersedia untuk agenda ini.',
                    ]);
                }

                if (($validated['leader_target'] ?? null) !== $proposedLeaderTarget) {
                    throw ValidationException::withMessages([
                        'leader_target' => 'Jika memilih orang yang diajukan OPD, tujuan disposisi harus mengikuti target pengajuan.',
                    ]);
                }
            }

            if (($validated['attendance_source'] ?? null) === 'disposition'
                && $proposedLeaderTarget
                && ($validated['leader_target'] ?? null) === $proposedLeaderTarget) {
                throw ValidationException::withMessages([
                    'leader_target' => 'Jika memilih orang hasil disposisi, target orang yang diajukan OPD tidak boleh dipilih.',
                ]);
            }
        }

        $status = match ($validated['action']) {
            'approve' => 'awaiting_disposition',
            'needs_revision' => 'needs_revision',
            'rejected' => 'rejected',
        };

        DB::transaction(function () use ($request, $agenda, $validated, $status): void {
            $agenda->update([
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_note' => $validated['note'] ?? null,
                'attendance_source' => $validated['action'] === 'approve' ? ($validated['attendance_source'] ?? null) : null,
                'leader_target' => $validated['action'] === 'approve' ? ($validated['leader_target'] ?? null) : $agenda->leader_target,
                'delegate_name' => $validated['action'] === 'approve' && (($validated['leader_target'] ?? null) === 'other_official') ? ($validated['delegate_name'] ?? null) : null,
                'delegate_title' => $validated['action'] === 'approve' && (($validated['leader_target'] ?? null) === 'other_official') ? ($validated['delegate_title'] ?? null) : null,
            ]);

            $agenda->verifications()->create([
                'verified_by' => $request->user()->id,
                'action' => $validated['action'],
                'note' => $validated['note'] ?? 'Verifikasi oleh super admin.',
                'metadata' => ['source' => 'web', 'status' => $status],
            ]);
        });

        return to_route('agendas.index')->with('status', 'Verifikasi agenda berhasil diperbarui.');
    }

    public function sendInvitation(Request $request, Agenda $agenda): RedirectResponse
    {
        abort_unless($request->user() && ! $request->user()->hasRole(UserRole::Opd), 403);

        if (! $agenda->submitter?->hasRole(UserRole::Opd) || ! $agenda->verified_at || ! $agenda->invitation_letter_path) {
            return back()->with('status', 'Undangan belum bisa dikirim untuk agenda ini.');
        }

        $validated = $request->validate([
            'opd_ids' => ['required', 'array', 'min:1'],
            'opd_ids.*' => ['integer', Rule::exists('opds', 'id')],
            'modal' => ['nullable', 'string'],
        ], [
            'opd_ids.required' => 'Pilih minimal satu OPD tujuan undangan.',
            'opd_ids.min' => 'Pilih minimal satu OPD tujuan undangan.',
        ]);

        $recipients = User::query()
            ->whereIn('opd_id', $validated['opd_ids'])
            ->get();

        $recipientOpds = Opd::query()
            ->whereIn('id', $validated['opd_ids'])
            ->orderBy('name')
            ->get(['id', 'name']);

        DB::transaction(function () use ($agenda, $recipients, $recipientOpds, $request): void {
            foreach ($recipients as $recipient) {
                AppNotification::create([
                    'user_id' => $recipient->id,
                    'type' => 'agenda_invitation_sent',
                    'title' => 'Undangan agenda telah dikirim',
                    'body' => sprintf('Undangan untuk agenda "%s" telah dikirim ke OPD Anda.', $agenda->title),
                    'data' => [
                        'agenda_id' => $agenda->id,
                        'status' => $agenda->status,
                        'invitation_url' => asset('storage/'.$agenda->invitation_letter_path),
                    ],
                    'channels' => ['in_app', 'email'],
                    'scheduled_for' => now(),
                    'sent_at' => now(),
                ]);
            }

            $agenda->verifications()->create([
                'verified_by' => $request->user()->id,
                'action' => 'invitation_sent',
                'note' => 'Undangan dikirim ke OPD terpilih.',
                'metadata' => [
                    'source' => 'web',
                    'recipient_opd_ids' => $recipientOpds->pluck('id')->all(),
                    'recipient_opd_names' => $recipientOpds->pluck('name')->all(),
                    'recipient_count' => $recipients->count(),
                ],
            ]);
        });

        return to_route('agendas.index')->with('status', 'Undangan berhasil dikirim ke OPD yang dipilih.');
    }

    public function updateProkopimStatus(Request $request, Agenda $agenda): RedirectResponse|JsonResponse
    {
        abort_unless($request->user() && $request->user()->hasRole(UserRole::AdminProkopim, UserRole::SuperAdmin), 403);

        try {
            Log::info('updateProkopimStatus called', ['user_id' => $request->user()?->id, 'agenda_id' => $agenda->id, 'payload' => $request->all()]);

            $validated = $request->validate([
                'visual_status_ak' => ['nullable', Rule::in(['Belum', 'menunggu', 'sedang dikerjakan', 'selesai'])],
                'video_status_ak' => ['nullable', Rule::in(['Belum', 'menunggu', 'sedang dikerjakan', 'selesai'])],
                'visual_status_prokopim' => ['nullable', Rule::in(['belum ada bahan', 'sudah ada bahan dokumentasi', 'belum diperiksa', 'perlu revisi', 'ok tayang'])],
                'video_status_prokopim' => ['nullable', Rule::in(['belum ada bahan', 'sudah ada bahan dokumentasi', 'belum diperiksa', 'perlu revisi', 'ok tayang'])],
                'visual_content' => ['nullable', 'string', 'max:5000'],
                'visual_content_status' => ['nullable', Rule::in(['draft', 'fix'])],
                'visual_published_link' => ['nullable', 'string', 'max:500'],
                'video_content' => ['nullable', 'string', 'max:5000'],
                'video_content_status' => ['nullable', Rule::in(['draft', 'fix'])],
                'video_published_link' => ['nullable', 'string', 'max:500'],
            ]);

            $update = [];
            if (array_key_exists('visual_status_ak', $validated)) {
                $update['visual_status_ak'] = $validated['visual_status_ak'];
            }
            if (array_key_exists('video_status_ak', $validated)) {
                $update['video_status_ak'] = $validated['video_status_ak'];
            }
            if (array_key_exists('visual_status_prokopim', $validated)) {
                $update['visual_status_prokopim'] = $validated['visual_status_prokopim'];
            }
            if (array_key_exists('video_status_prokopim', $validated)) {
                $update['video_status_prokopim'] = $validated['video_status_prokopim'];
            }
            if (array_key_exists('visual_content', $validated)) {
                $update['visual_content'] = $validated['visual_content'];
            }
            if (array_key_exists('visual_content_status', $validated)) {
                $update['visual_content_status'] = $validated['visual_content_status'];
            }
            if (array_key_exists('visual_published_link', $validated)) {
                $update['visual_published_link'] = $validated['visual_published_link'];
            }
            if (array_key_exists('video_content', $validated)) {
                $update['video_content'] = $validated['video_content'];
            }
            if (array_key_exists('video_content_status', $validated)) {
                $update['video_content_status'] = $validated['video_content_status'];
            }
            if (array_key_exists('video_published_link', $validated)) {
                $update['video_published_link'] = $validated['video_published_link'];
            }

            Log::info('updateProkopimStatus validated', ['validated' => $validated]);

            if (! empty($update)) {
                $agenda->update($update);

                $agenda->verifications()->create([
                    'verified_by' => $request->user()->id,
                    'action' => 'prokopim_status_updated',
                    'note' => 'Status/Konten Prokopim diperbarui oleh '.$request->user()->name,
                    'metadata' => $update,
                ]);
                Log::info('updateProkopimStatus updated_agenda', ['agenda_id' => $agenda->id, 'update' => $update]);
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'updated' => $update,
                ]);
            }

            return back()->with('status', 'Status/Konten Prokopim berhasil diperbarui.');
        } catch (ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            throw $e;
        }
    }
}
