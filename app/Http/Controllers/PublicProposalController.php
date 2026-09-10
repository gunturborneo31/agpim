<?php

namespace App\Http\Controllers;

use App\Events\AgendaSubmitted;
use App\Http\Requests\PublicStoreAgendaRequest;
use App\Models\Agenda;
use App\Models\AgendaType;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicProposalController extends Controller
{
    public function create(): View
    {
        $existingOpdNames = Opd::query()->orderBy('name')->pluck('name')->all();
        $opdOptions = array_values(array_unique(array_merge($this->presetOpdNames(), $existingOpdNames)));

        return view('public-proposals.create', [
            'agendaTypes' => AgendaType::query()->orderBy('name')->get(),
            'opdOptions' => $opdOptions,
        ]);
    }

    public function store(PublicStoreAgendaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $opd = $this->resolveOpdFromSelection($validated);
        $timeUnknown = (bool) ($validated['time_unknown'] ?? false);

        $invitationPath = $request->file('invitation_letter_path')->store('agendas/invitations', 'public');
        $speechPath = $request->hasFile('speech_draft_path')
            ? $request->file('speech_draft_path')->store('agendas/speeches', 'public')
            : null;

        $email = sprintf('%s@agpim.local', Str::slug($validated['submitter_name']));

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $validated['submitter_name'],
                'opd_id' => $opd->id,
                'phone' => $validated['submitter_phone'] ?? null,
                'role' => 'opd',
                'password' => Hash::make(Str::random(40)),
            ],
        );

        if ($user->role === 'opd') {
            $user->forceFill([
                'name' => $validated['submitter_name'],
                'opd_id' => $opd->id,
                'phone' => $validated['submitter_phone'] ?? $user->phone,
            ])->save();
        }

        $agenda = DB::transaction(function () use ($validated, $invitationPath, $speechPath, $user, $opd, $timeUnknown) {
            $agenda = Agenda::create([
                'opd_id' => $opd->id,
                'agenda_type_id' => $validated['agenda_type_id'],
                'submitted_by' => $user->id,
                'title' => $validated['title'],
                'event_date' => $validated['event_date'],
                'start_time' => $timeUnknown ? '00:00' : $validated['start_time'],
                'end_time' => $timeUnknown ? '00:00' : $validated['end_time'],
                'time_unknown' => $timeUnknown,
                'location' => $validated['location'],
                'description' => $validated['description'],
                'priority' => $validated['priority'] ?? 'biasa',
                'leader_target' => $validated['leader_target'],
                'person_in_charge' => $validated['person_in_charge'] ?? null,
                'pic_phone' => $validated['pic_phone'] ?? null,
                'invitation_letter_path' => $invitationPath,
                'speech_draft_path' => $speechPath,
                'speech_draft_note' => $validated['speech_draft_note'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
                'is_internal_public' => false,
            ]);

            $agenda->verifications()->create([
                'verified_by' => $user->id,
                'action' => 'submitted',
                'note' => 'Pengajuan dibuat melalui usulan tanpa login.',
                'metadata' => ['source' => 'public_form'],
            ]);

            return $agenda;
        });

        AgendaSubmitted::dispatch($agenda->load('opd'), $user);

        return to_route('public-proposals.create')->with('status', 'Usulan agenda berhasil dikirim.');
    }

    private function presetOpdNames(): array
    {
        return [
            'Dinas Kesehatan, Pengendalian Penduduk dan KB',
            'Dinas Pekerjaan Umum dan Penataan Ruang, Perumahan dan Kawasan Pemukiman',
            'Satuan Polisi Pamong Praja',
            'Badan Penanggulangan Bencana Daerah',
            'Dinas Sosial, Pemberdayaan Perempuan Perlindungan Anak',
            'Dinas Lingkungan Hidup',
            'Dinas Kependudukan dan Pencatatan Sipil',
            'Dinas Pemberdayaan Masyarakat dan Pemerintahan Kampung',
            'Dinas Perhubungan',
            'Dinas Komunikasi dan Informatika, Statistik, dan Persandian',
            'Dinas Penanaman Modal dan Pelayanan Perijinan Terpadu',
            'Dinas Pariwisata, Pemuda dan Olahraga',
            'Dinas Ketahanan Pangan dan Pertanian',
            'Sekretariat DPRD',
            'Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah',
            'Badan Pendapatan Daerah',
            'Badan Pengelola Keuangan dan Aset Daerah',
            'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',
            'Badan Pengelola Perbatasan Daerah',
            'Inspektorat',
            'Kecamatan Long Apari',
            'Kecamatan Long Pahangai',
            'Kecamatan Long Bagun',
            'Kecamatan Laham',
            'Kecamatan Long Hubung',
            'Badan Kesatuan Bangsa dan Politik',
            'Bagian Pemerintahan',
            'Bagian Kesejahteraan Rakyat',
            'Bagian Hukum',
            'Bagian Perekonomian dan Sumber Daya Alam',
            'Bagian Administrasi Pembangunan',
            'Bagian Pengadaan Barang Dan Jasa',
            'Bagian Umum',
            'Bagian Protokol dan Komunikasi Pimpinan',
            'Rumah Sakit Pratama Nawacita Datah Dave',
            'Rumah Sakit Pratama Gerbang Sehat Mahakam Ulu',
            'Puskesmas Laham',
            'Puskesmas Memahak Besar',
            'Puskesmas Long Apari',
            'Puskesmas Long Pahangai',
            'Puskesmas Long Bagun',
            'Puskesmas Long Hubung',
            'Bagian Organisasi',
        ];
    }

    private function resolveOpdFromSelection(array $validated): Opd
    {
        $selectedName = trim((string) ($validated['opd_option'] ?? ''));

        if ($selectedName === 'lainnya') {
            $customName = trim((string) ($validated['opd_custom_name'] ?? ''));

            return $this->resolveOrCreateOpd($customName);
        }

        return $this->resolveOrCreateOpd($selectedName);
    }

    private function resolveOrCreateOpd(string $name): Opd
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new \InvalidArgumentException('Nama OPD wajib diisi.');
        }

        $existingOpd = Opd::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->first();

        if ($existingOpd) {
            return $existingOpd;
        }

        $baseCode = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', Str::slug($normalizedName)) ?: 'OPD');
        $code = $baseCode !== '' ? $baseCode : 'OPD';
        $counter = 1;

        while (Opd::query()->where('code', $code)->exists()) {
            $code = $baseCode.$counter;
            $counter++;
        }

        return Opd::create([
            'code' => $code,
            'name' => $normalizedName,
            'is_active' => true,
        ]);
    }
}
