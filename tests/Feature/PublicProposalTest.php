<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AgendaType;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PublicProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_proposal_can_create_custom_opd_when_other_is_selected(): void
    {
        $this->seed();

        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->post('/usulan-tanpa-login', [
            'submitter_name' => 'Budi Santoso',
            'submitter_email' => 'budi@example.com',
            'submitter_phone' => '081234567890',
            'opd_option' => 'lainnya',
            'opd_custom_name' => 'Dinas Uji Coba',
            'agenda_type_id' => $agendaType->id,
            'title' => 'Kegiatan Uji Coba',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Uji',
            'description' => 'Pengujian form usulan tanpa login.',
            'priority' => 'biasa',
            'leader_target' => 'sekda',
            'person_in_charge' => 'Tim Penguji',
            'pic_phone' => '081234567891',
            'invitation_letter_path' => UploadedFile::fake()->create('undangan.pdf', 120, 'application/pdf'),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('opds', [
            'name' => 'Dinas Uji Coba',
        ]);

        $opd = Opd::query()->where('name', 'Dinas Uji Coba')->firstOrFail();

        $this->assertDatabaseHas('agendas', [
            'title' => 'Kegiatan Uji Coba',
            'opd_id' => $opd->id,
        ]);
    }

    public function test_public_proposal_requires_submitter_phone(): void
    {
        $this->seed();

        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->post('/usulan-tanpa-login', [
            'submitter_name' => 'Budi Santoso',
            'opd_option' => 'lainnya',
            'opd_custom_name' => 'Dinas Uji Coba Tanpa Telepon',
            'agenda_type_id' => $agendaType->id,
            'title' => 'Kegiatan Tanpa Nomor HP',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Uji',
            'description' => 'Pengujian validasi nomor HP pengusul wajib.',
            'priority' => 'biasa',
            'leader_target' => 'sekda',
            'invitation_letter_path' => UploadedFile::fake()->create('undangan.pdf', 120, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors(['submitter_phone']);
    }

    public function test_public_proposal_can_mark_time_as_unknown(): void
    {
        $this->seed();

        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->post('/usulan-tanpa-login', [
            'submitter_name' => 'Siti Aminah',
            'submitter_phone' => '081234567892',
            'opd_option' => 'lainnya',
            'opd_custom_name' => 'Dinas Uji Coba Waktu',
            'agenda_type_id' => $agendaType->id,
            'title' => 'Kegiatan Waktu Belum Ditentukan',
            'event_date' => now()->addDay()->toDateString(),
            'time_unknown' => '1',
            'location' => 'Ruang Uji',
            'description' => 'Pengujian waktu belum diketahui.',
            'priority' => 'biasa',
            'leader_target' => 'sekda',
            'invitation_letter_path' => UploadedFile::fake()->create('undangan.pdf', 120, 'application/pdf'),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('agendas', [
            'title' => 'Kegiatan Waktu Belum Ditentukan',
            'time_unknown' => true,
        ]);

        $agenda = Agenda::query()->where('title', 'Kegiatan Waktu Belum Ditentukan')->firstOrFail();
        $this->assertSame('P.M - P.M', $agenda->time_range_display);
    }

    public function test_public_proposal_saves_speech_draft_note(): void
    {
        $this->seed();

        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->post('/usulan-tanpa-login', [
            'submitter_name' => 'Dewi Lestari',
            'submitter_phone' => '081234567893',
            'opd_option' => 'lainnya',
            'opd_custom_name' => 'Dinas Uji Coba Catatan',
            'agenda_type_id' => $agendaType->id,
            'title' => 'Kegiatan Dengan Catatan Draft',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Uji',
            'description' => 'Pengujian catatan draft materi/sambutan.',
            'priority' => 'biasa',
            'leader_target' => 'sekda',
            'invitation_letter_path' => UploadedFile::fake()->create('undangan.pdf', 120, 'application/pdf'),
            'speech_draft_note' => 'Mohon fokus pada capaian program tahun ini.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('agendas', [
            'title' => 'Kegiatan Dengan Catatan Draft',
            'speech_draft_note' => 'Mohon fokus pada capaian program tahun ini.',
        ]);
    }
}
