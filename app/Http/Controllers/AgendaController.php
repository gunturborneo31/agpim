<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Events\AgendaSubmitted;
use App\Http\Requests\StoreAgendaRequest;
use App\Http\Requests\UpdateAgendaRequest;
use App\Models\Agenda;
use App\Models\AgendaRevisionLink;
use App\Models\AgendaType;
use App\Models\AppNotification;
use App\Models\Opd;
use App\Models\User;
use App\Support\WaPhoneFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        // Verifikator langsung melihat antrean "Diajukan", yang paling dekat tanggalnya lebih dulu.
        // ?status=all menampilkan semua status.
        if ($isVerificationMode && $request->query('status') === null) {
            $status = 'submitted';
            if ($request->query('sort') === null) {
                $sort = 'oldest';
            }
        }
        if ($status === 'all') {
            $status = '';
        }

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
            'status_asc' => fn ($query) => $query->orderByRaw("CASE status WHEN 'draft' THEN 1 WHEN 'submitted' THEN 2 WHEN 'under_review' THEN 3 WHEN 'approved' THEN 4 WHEN 'needs_revision' THEN 5 WHEN 'rejected' THEN 6 WHEN 'cancelled' THEN 7 WHEN 'completed' THEN 8 ELSE 9 END"),
            'status_desc' => fn ($query) => $query->orderByRaw("CASE status WHEN 'completed' THEN 1 WHEN 'cancelled' THEN 2 WHEN 'rejected' THEN 3 WHEN 'needs_revision' THEN 4 WHEN 'approved' THEN 5 WHEN 'under_review' THEN 6 WHEN 'submitted' THEN 7 WHEN 'draft' THEN 8 ELSE 9 END"),
        ];

        if (! array_key_exists($sort, $sortOptions)) {
            $sort = 'latest';
        }

        $agendaQuery = Agenda::query()
            ->with(['opd', 'type', 'submitter'])
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
            ->when($opdId > 0, fn ($query) => $query->where('opd_id', $opdId))
            ->when($startDate !== '', fn ($query) => $query->whereDate('event_date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('event_date', '<=', $endDate))
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority));

        // Jumlah per status (mengikuti filter lain) untuk tab di atas tabel.
        $statusCounts = (clone $agendaQuery)
            ->reorder()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total);

        $agendaQuery->when($status !== '', fn ($query) => $query->where('status', $status));

        $sortOptions[$sort]($agendaQuery);

        $agendas = $agendaQuery->paginate(10)->withQueryString();

        return view('agendas.index', [
            'agendas' => $agendas,
            'agendaTypes' => AgendaType::query()->orderBy('name')->get(),
            'opdOptions' => Opd::query()->orderBy('name')->get(['id', 'name']),
            'isVerificationMode' => $isVerificationMode,
            'statusCounts' => $statusCounts,
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
            'agenda' => $agenda->load(['opd', 'type', 'submitter', 'reviewer', 'followUps.attachments']),
            'conflicts' => $agenda->conflicts(),
        ]);
    }

    public function edit(Request $request, Agenda $agenda): View
    {
        Gate::authorize('update', $agenda);

        if (! in_array($agenda->status, ['submitted', 'needs_revision'], true)) {
            abort(403);
        }

        return view('agendas.edit', [
            'agenda' => $agenda->load(['type']),
            'agendaTypes' => AgendaType::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAgendaRequest $request, Agenda $agenda): RedirectResponse
    {
        if (! in_array($agenda->status, ['submitted', 'needs_revision'], true)) {
            abort(403);
        }

        $validated = $request->validated();

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

        // Pengajuan yang diminta revisi otomatis diajukan ulang setelah diperbaiki.
        $isResubmission = $agenda->status === 'needs_revision';
        if ($isResubmission) {
            $validated['status'] = 'submitted';
            $validated['submitted_at'] = now();
        }

        DB::transaction(function () use ($request, $agenda, $validated, $isResubmission): void {
            $agenda->update($validated);

            $agenda->verifications()->create([
                'verified_by' => $request->user()->id,
                'action' => $isResubmission ? 'resubmitted' : 'updated_submission',
                'note' => $isResubmission
                    ? 'Pengajuan direvisi dan diajukan ulang.'
                    : 'Pengajuan diperbarui saat status masih diajukan.',
                'metadata' => ['source' => 'web'],
            ]);
        });

        return to_route('agendas.index')->with('status', $isResubmission
            ? 'Revisi tersimpan. Pengajuan diajukan ulang untuk diverifikasi.'
            : 'Pengajuan agenda berhasil diperbarui.');
    }

    public function verify(Request $request, Agenda $agenda): RedirectResponse|JsonResponse
    {
        abort_unless($request->user() && ! $request->user()->hasRole(UserRole::Opd), 403);

        if (! $agenda->submitter?->hasRole(UserRole::Opd)) {
            $message = 'Agenda ini tidak tersedia untuk verifikasi super admin.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('status', $message);
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'needs_revision', 'rejected', 'cancel'])],
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
            'approve' => 'approved',
            'needs_revision' => 'needs_revision',
            'rejected' => 'rejected',
            'cancel' => 'cancelled',
        };

        DB::transaction(function () use ($request, $agenda, $validated, $status): void {
            $attributes = [
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_note' => $validated['note'] ?? null,
            ];

            if ($validated['action'] === 'approve') {
                $toOther = ($validated['leader_target'] ?? null) === 'other_official';
                $attributes += [
                    'decided_at' => now(),
                    'attendance_source' => $validated['attendance_source'] ?? null,
                    'leader_target' => $validated['leader_target'] ?? null,
                    'delegate_name' => $toOther ? ($validated['delegate_name'] ?? null) : null,
                    'delegate_title' => $toOther ? ($validated['delegate_title'] ?? null) : null,
                ];
            } elseif ($validated['action'] !== 'cancel') {
                // Perlu revisi / ditolak: keputusan disposisi sebelumnya tidak berlaku lagi.
                $attributes += [
                    'decided_at' => null,
                    'attendance_source' => null,
                    'delegate_name' => null,
                    'delegate_title' => null,
                ];
            }
            // Dibatalkan: data disposisi dibiarkan sebagai riwayat.

            $agenda->update($attributes);

            $agenda->verifications()->create([
                'verified_by' => $request->user()->id,
                'action' => $validated['action'],
                'note' => $validated['note'] ?? 'Verifikasi oleh super admin.',
                'metadata' => ['source' => 'web', 'status' => $status],
            ]);
        });

        if ($request->expectsJson()) {
            $agenda->refresh()->load('submitter');
            $statusLabel = config('agpim.statuses')[$agenda->status] ?? $agenda->status;

            return response()->json([
                'message' => 'Keputusan tersimpan: '.$statusLabel.'.',
                'status' => $agenda->status,
                'status_label' => $statusLabel,
                'status_html' => Blade::render('<x-ui.status-pill :status="$status" :text="$text" />', ['status' => $agenda->status, 'text' => $statusLabel]),
                'whatsapp_number' => $this->decisionPhone($agenda),
            ]);
        }

        return to_route('agendas.index')->with('status', 'Verifikasi agenda berhasil diperbarui.');
    }

    /**
     * Usulan yang masuk (atau diajukan ulang) sejak waktu tertentu. Dipanggil berkala
     * oleh halaman Agenda admin untuk notifikasi otomatis tanpa refresh.
     */
    public function incoming(Request $request): JsonResponse
    {
        abort_unless($request->user() && ! $request->user()->hasRole(UserRole::Opd), 403);

        $now = now();
        try {
            $since = $request->filled('since') ? Carbon::parse((string) $request->query('since')) : $now;
        } catch (\Throwable) {
            $since = $now;
        }

        $fromOpd = fn ($query) => $query
            ->where('status', 'submitted')
            ->whereHas('submitter', fn ($q) => $q->where('role', UserRole::Opd->value));

        $items = Agenda::query()
            ->with('opd')
            ->where($fromOpd)
            ->where('submitted_at', '>=', $since)
            ->orderBy('submitted_at')
            ->limit(10)
            ->get()
            ->map(fn (Agenda $agenda) => [
                'id' => $agenda->id,
                'title' => $agenda->title,
                'opd' => $agenda->opd->alias ?? $agenda->opd->name ?? '-',
                'submitted_at' => $agenda->submitted_at?->toIso8601String(),
            ]);

        return response()->json([
            'now' => $now->toIso8601String(),
            'pending' => Agenda::query()->where($fromOpd)->count(),
            'items' => $items,
        ]);
    }

    /**
     * Siapkan pesan WhatsApp berisi status pengajuan. Untuk status "Perlu revisi",
     * sekaligus buat link revisi sekali pakai (link lama otomatis tidak berlaku).
     */
    public function whatsappStatus(Request $request, Agenda $agenda): JsonResponse
    {
        abort_unless($request->user() && ! $request->user()->hasRole(UserRole::Opd), 403);

        $agenda->load('submitter');
        $phone = $this->decisionPhone($agenda);
        if (! $phone) {
            return response()->json(['message' => 'Nomor WhatsApp pengaju tidak tersedia pada pengajuan ini.'], 422);
        }

        $revisionUrl = null;
        if ($agenda->status === 'needs_revision') {
            $token = AgendaRevisionLink::issueFor($agenda, $request->user());
            $revisionUrl = route('revisions.edit', $token);

            $agenda->verifications()->create([
                'verified_by' => $request->user()->id,
                'action' => 'revision_link_sent',
                'note' => 'Link revisi dikirim lewat WhatsApp.',
                'metadata' => ['source' => 'web', 'phone' => $phone],
            ]);
        }

        $statusLabel = config('agpim.statuses')[$agenda->status] ?? $agenda->status;

        return response()->json([
            'url' => $this->decisionWhatsappUrl($agenda, $statusLabel, $phone, $revisionUrl),
            'number' => $phone,
            'revision_link' => $revisionUrl !== null,
        ]);
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

    /** Nomor WA tujuan pemberitahuan keputusan: nomor PIC pada pengajuan, lalu nomor akun pengaju. */
    private function decisionPhone(Agenda $agenda): ?string
    {
        foreach ([$agenda->pic_phone, $agenda->submitter?->phone] as $candidate) {
            $normalized = $candidate ? WaPhoneFormatter::normalizeIndonesian((string) $candidate) : null;
            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    /** Link wa.me berisi pesan status pengajuan yang siap dikirim. */
    private function decisionWhatsappUrl(Agenda $agenda, string $statusLabel, string $phone, ?string $revisionUrl = null): string
    {
        $lines = [
            'Yth. '.($agenda->person_in_charge ?: ($agenda->submitter?->name ?? 'Bapak/Ibu')).',',
            '',
            'Pengajuan agenda *'.$agenda->title.'*',
            'Tanggal: '.$agenda->event_date->translatedFormat('l, j F Y').($agenda->time_unknown ? '' : ', pukul '.$agenda->time_range_display),
            'Tempat: '.$agenda->location,
            '',
            'Status: *'.match ($agenda->status) {
                'submitted' => 'Diajukan (sedang diverifikasi Prokopim)',
                'needs_revision' => 'Perlu revisi',
                'rejected' => 'Ditolak',
                default => $statusLabel,
            }.'*',
        ];

        if ($agenda->status === 'approved' && $agenda->leader_target) {
            $lines[] = 'Disposisi: '.(config('agpim.dispositions')[$agenda->leader_target] ?? $agenda->leader_target)
                .($agenda->delegate_name ? ' ('.$agenda->delegate_name.($agenda->delegate_title ? ', '.$agenda->delegate_title : '').')' : '');
        }

        if ($agenda->verification_note) {
            $lines[] = ($agenda->status === 'cancelled' ? 'Alasan pembatalan: ' : 'Catatan: ').$agenda->verification_note;
        }

        if ($agenda->status === 'needs_revision') {
            $lines[] = '';
            if ($revisionUrl) {
                $lines[] = 'Silakan perbaiki pengajuan melalui link berikut:';
                $lines[] = $revisionUrl;
                $lines[] = '_Link hanya bisa dipakai satu kali dan berlaku '.AgendaRevisionLink::VALID_DAYS.' hari._';
            } else {
                $lines[] = 'Silakan perbaiki pengajuan melalui aplikasi AGPIM, lalu simpan untuk mengajukan ulang.';
            }
        }

        $lines[] = '';
        $lines[] = 'Terima kasih.';
        $lines[] = 'Prokopim Kabupaten Mahakam Ulu';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(implode("\n", $lines));
    }
}
