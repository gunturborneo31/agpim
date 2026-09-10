<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AgendaType;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AgendaSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_opd_user_sees_only_own_agendas_in_list(): void
    {
        $this->seed();

        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $otherOpd = User::factory()->opd($opdUser->opd_id)->create([
            'name' => 'Operator OPD Lain',
            'email' => 'operator-lain@agpim.test',
        ]);

        $agendaType = AgendaType::query()->firstOrFail();

        Agenda::create([
            'opd_id' => $otherOpd->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $otherOpd->id,
            'title' => 'Agenda OPD Lain',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Lain',
            'description' => 'Agenda milik user OPD lain.',
            'priority' => 'biasa',
            'status' => 'submitted',
            'invitation_letter_path' => 'documents/agenda-opd-lain.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($opdUser)->get('/agendas');

        $response
            ->assertOk()
            ->assertDontSee('Agenda OPD Lain')
            ->assertSee('Rapat Infrastruktur Digital');
    }

    public function test_opd_user_can_submit_agenda(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($user)->post('/agendas', [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Sosialisasi Smart Village',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Balai Pertemuan',
            'description' => 'Sosialisasi transformasi digital kampung.',
            'priority' => 'sedang',
            'leader_target' => 'sekda',
            'person_in_charge' => 'Kabid Infrastruktur',
            'pic_phone' => '081111111111',
            'invitation_letter_path' => UploadedFile::fake()->create('invitation-smart-village.pdf', 120, 'application/pdf'),
            'speech_draft_path' => UploadedFile::fake()->create('draft-sambutan.pdf', 80, 'application/pdf'),
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'title' => 'Sosialisasi Smart Village',
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'type' => 'agenda_submitted',
        ]);
        $this->assertSame(5, Agenda::count());
    }

    public function test_opd_user_can_submit_agenda_with_time_unknown(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($user)->post('/agendas', [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Kegiatan Tanpa Jam Pasti',
            'event_date' => now()->addDays(3)->toDateString(),
            'time_unknown' => '1',
            'location' => 'Balai Pertemuan',
            'description' => 'Kegiatan yang jadwal jamnya belum ditentukan.',
            'priority' => 'sedang',
            'leader_target' => 'sekda',
            'invitation_letter_path' => UploadedFile::fake()->create('invitation-time-unknown.pdf', 120, 'application/pdf'),
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'title' => 'Kegiatan Tanpa Jam Pasti',
            'time_unknown' => true,
        ]);

        $agenda = Agenda::query()->where('title', 'Kegiatan Tanpa Jam Pasti')->firstOrFail();
        $this->assertSame('P.M - P.M', $agenda->time_range_display);
    }

    public function test_opd_user_can_submit_agenda_with_speech_draft_note(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($user)->post('/agendas', [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Kegiatan Dengan Catatan Sambutan',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Balai Pertemuan',
            'description' => 'Kegiatan untuk menguji catatan draft sambutan.',
            'priority' => 'sedang',
            'leader_target' => 'sekda',
            'invitation_letter_path' => UploadedFile::fake()->create('invitation-note.pdf', 120, 'application/pdf'),
            'speech_draft_note' => 'Tekankan capaian program unggulan.',
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'title' => 'Kegiatan Dengan Catatan Sambutan',
            'speech_draft_note' => 'Tekankan capaian program unggulan.',
        ]);
    }

    public function test_non_opd_role_is_forbidden_to_submit_agenda(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'bupati@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($user)->post('/agendas', [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Agenda Tidak Sah',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Kerja',
            'description' => 'Tidak boleh lolos.',
            'priority' => 'biasa',
            'invitation_letter_path' => 'documents/invalid.pdf',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_prokopim_role_is_forbidden_to_submit_agenda(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'prokopim@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($user)->post('/agendas', [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Agenda Admin Prokopim',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Kerja',
            'description' => 'Tidak boleh lewat endpoint pengajuan.',
            'priority' => 'biasa',
            'leader_target' => 'sekda',
            'invitation_letter_path' => UploadedFile::fake()->create('invalid-admin-prokopim.pdf', 80, 'application/pdf'),
        ]);

        $response->assertForbidden();
    }

    public function test_super_admin_cannot_submit_agenda(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'superadmin@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($user)->post('/agendas', [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Agenda Super Admin',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Kerja',
            'description' => 'Tidak boleh lewat endpoint pengajuan.',
            'priority' => 'biasa',
            'invitation_letter_path' => UploadedFile::fake()->create('invalid-superadmin.pdf', 80, 'application/pdf'),
        ]);

        $response->assertForbidden();
    }

    public function test_super_admin_can_verify_submitted_opd_agenda(): void
    {
        $this->seed();

        $superAdmin = User::query()->where('email', 'superadmin@agpim.test')->firstOrFail();
        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opdUser->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $opdUser->id,
            'title' => 'Agenda Menunggu Verifikasi',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Verifikasi',
            'description' => 'Agenda untuk diverifikasi super admin.',
            'priority' => 'sedang',
            'status' => 'submitted',
            'invitation_letter_path' => 'documents/agenda-verify.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)->post("/agendas/{$agenda->id}/verify", [
            'action' => 'approve',
            'attendance_source' => 'disposition',
            'leader_target' => 'other_official',
            'delegate_name' => 'Dr. Rina Saputri',
            'delegate_title' => 'Asisten Administrasi Umum',
            'note' => 'Data lengkap dan disetujui ke tahap disposisi.',
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'status' => 'awaiting_disposition',
            'reviewed_by' => $superAdmin->id,
            'attendance_source' => 'disposition',
            'leader_target' => 'other_official',
            'delegate_name' => 'Dr. Rina Saputri',
            'delegate_title' => 'Asisten Administrasi Umum',
        ]);

        $this->assertDatabaseHas('agenda_verifications', [
            'agenda_id' => $agenda->id,
            'verified_by' => $superAdmin->id,
            'action' => 'approve',
        ]);
    }

    public function test_admin_prokopim_can_verify_submitted_opd_agenda(): void
    {
        $this->seed();

        $adminProkopim = User::query()->where('email', 'prokopim@agpim.test')->firstOrFail();
        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opdUser->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $opdUser->id,
            'title' => 'Agenda Verifikasi Admin Prokopim',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Verifikasi',
            'description' => 'Agenda untuk diverifikasi admin prokopim.',
            'priority' => 'sedang',
            'status' => 'submitted',
            'leader_target' => 'sekda',
            'invitation_letter_path' => 'documents/agenda-verify-admin-prokopim.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($adminProkopim)->post("/agendas/{$agenda->id}/verify", [
            'action' => 'approve',
            'attendance_source' => 'proposed',
            'leader_target' => 'sekda',
            'note' => 'Diverifikasi admin prokopim.',
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'status' => 'awaiting_disposition',
            'reviewed_by' => $adminProkopim->id,
        ]);
    }

    public function test_super_admin_approval_requires_attendance_and_disposition_decision(): void
    {
        $this->seed();

        $superAdmin = User::query()->where('email', 'superadmin@agpim.test')->firstOrFail();
        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opdUser->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $opdUser->id,
            'title' => 'Agenda Butuh Keputusan Kehadiran',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Verifikasi',
            'description' => 'Agenda untuk uji validasi keputusan verifikasi.',
            'priority' => 'sedang',
            'status' => 'submitted',
            'invitation_letter_path' => 'documents/agenda-verify-2.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->from('/agendas')->actingAs($superAdmin)->post("/agendas/{$agenda->id}/verify", [
            'action' => 'approve',
            'note' => 'Akan ditolak validasi karena field keputusan belum diisi.',
        ]);

        $response->assertRedirect('/agendas');
        $response->assertSessionHasErrors(['attendance_source', 'leader_target']);
    }

    public function test_super_admin_disposition_choice_cannot_use_opd_proposed_target(): void
    {
        $this->seed();

        $superAdmin = User::query()->where('email', 'superadmin@agpim.test')->firstOrFail();
        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opdUser->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $opdUser->id,
            'title' => 'Agenda Uji Pemisahan Target',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Verifikasi',
            'description' => 'Agenda untuk memastikan target OPD tidak muncul pada mode disposisi.',
            'priority' => 'sedang',
            'status' => 'submitted',
            'leader_target' => 'sekda',
            'invitation_letter_path' => 'documents/agenda-verify-3.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->from('/agendas')->actingAs($superAdmin)->post("/agendas/{$agenda->id}/verify", [
            'action' => 'approve',
            'attendance_source' => 'disposition',
            'leader_target' => 'sekda',
            'note' => 'Harus gagal karena target ini adalah usulan OPD.',
        ]);

        $response->assertRedirect('/agendas');
        $response->assertSessionHasErrors(['leader_target']);
    }

    public function test_opd_user_can_update_submitted_agenda(): void
    {
        $this->seed();

        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opdUser->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $opdUser->id,
            'title' => 'Agenda Awal',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Lama',
            'description' => 'Deskripsi lama.',
            'priority' => 'sedang',
            'status' => 'submitted',
            'leader_target' => 'sekda',
            'invitation_letter_path' => 'documents/agenda-update.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($opdUser)->put("/agendas/{$agenda->id}", [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Agenda Setelah Edit',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:30',
            'location' => 'Ruang Baru',
            'description' => 'Deskripsi setelah diperbarui.',
            'priority' => 'tinggi',
            'leader_target' => 'bupati',
            'person_in_charge' => 'PIC Baru',
            'pic_phone' => '081299999999',
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'title' => 'Agenda Setelah Edit',
            'location' => 'Ruang Baru',
            'priority' => 'tinggi',
            'leader_target' => 'bupati',
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('agenda_verifications', [
            'agenda_id' => $agenda->id,
            'verified_by' => $opdUser->id,
            'action' => 'updated_submission',
        ]);
    }

    public function test_submitted_agenda_only_status_can_be_updated(): void
    {
        $this->seed();

        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opdUser->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $opdUser->id,
            'title' => 'Agenda Tidak Bisa Edit',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Verifikasi',
            'description' => 'Agenda bukan status diajukan.',
            'priority' => 'sedang',
            'status' => 'awaiting_disposition',
            'leader_target' => 'sekda',
            'invitation_letter_path' => 'documents/agenda-no-edit.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($opdUser)->put("/agendas/{$agenda->id}", [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Perubahan Tidak Sah',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'location' => 'Ruang Baru',
            'description' => 'Tidak boleh diubah.',
            'priority' => 'tinggi',
            'leader_target' => 'bupati',
        ]);

        $response->assertForbidden();
    }

    public function test_verifikator_can_verify_submitted_opd_agenda(): void
    {
        $this->seed();

        $verifikator = User::query()->where('email', 'verifikator@agpim.test')->firstOrFail();
        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opdUser->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $opdUser->id,
            'title' => 'Agenda Verifikasi Verifikator',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Verifikasi',
            'description' => 'Agenda usulan untuk diverifikasi oleh verifikator.',
            'priority' => 'sedang',
            'status' => 'submitted',
            'leader_target' => 'sekda',
            'invitation_letter_path' => 'documents/agenda-verify-verifikator.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($verifikator)->post("/agendas/{$agenda->id}/verify", [
            'action' => 'approve',
            'attendance_source' => 'proposed',
            'leader_target' => 'sekda',
            'note' => 'Diverifikasi oleh verifikator.',
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'status' => 'awaiting_disposition',
            'reviewed_by' => $verifikator->id,
        ]);
    }

    public function test_verifikator_cannot_verify_agenda_not_from_usulan(): void
    {
        $this->seed();

        $verifikator = User::query()->where('email', 'verifikator@agpim.test')->firstOrFail();
        $superAdmin = User::query()->where('email', 'superadmin@agpim.test')->firstOrFail();
        $opd = Opd::query()->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opd->id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $superAdmin->id,
            'title' => 'Agenda Bukan Usulan OPD',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Verifikasi',
            'description' => 'Agenda ini bukan berasal dari usulan OPD.',
            'priority' => 'sedang',
            'status' => 'submitted',
            'invitation_letter_path' => 'documents/agenda-not-usulan.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($verifikator)->post("/agendas/{$agenda->id}/verify", [
            'action' => 'approve',
            'attendance_source' => 'proposed',
            'leader_target' => 'sekda',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'status' => 'submitted',
        ]);
    }

    public function test_verifikator_can_add_kegiatan_directly_approved(): void
    {
        $this->seed();

        $verifikator = User::query()->where('email', 'verifikator@agpim.test')->firstOrFail();
        $opd = Opd::query()->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($verifikator)->post('/agendas', [
            'direct_approve' => '1',
            'opd_id' => $opd->id,
            'agenda_type_id' => $agendaType->id,
            'title' => 'Kegiatan Ditambahkan Verifikator',
            'event_date' => now()->addDays(4)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'location' => 'Aula Kantor Bupati',
            'description' => 'Kegiatan yang ditambahkan langsung oleh verifikator.',
            'priority' => 'tinggi',
            'leader_target' => 'bupati',
            'invitation_letter_path' => UploadedFile::fake()->create('kegiatan-verifikator.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'title' => 'Kegiatan Ditambahkan Verifikator',
            'opd_id' => $opd->id,
            'status' => 'approved',
        ]);
    }

    public function test_verifikator_cannot_edit_agenda(): void
    {
        $this->seed();

        $verifikator = User::query()->where('email', 'verifikator@agpim.test')->firstOrFail();
        $opdUser = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $agenda = Agenda::create([
            'opd_id' => $opdUser->opd_id,
            'agenda_type_id' => $agendaType->id,
            'submitted_by' => $opdUser->id,
            'title' => 'Agenda Tidak Boleh Diubah Verifikator',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Verifikasi',
            'description' => 'Verifikator tidak boleh mengedit agenda OPD.',
            'priority' => 'sedang',
            'status' => 'submitted',
            'invitation_letter_path' => 'documents/agenda-no-edit-verifikator.pdf',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($verifikator)->put("/agendas/{$agenda->id}", [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Perubahan Tidak Sah',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'location' => 'Ruang Baru',
            'description' => 'Tidak boleh diubah.',
            'priority' => 'tinggi',
            'leader_target' => 'bupati',
        ]);

        $response->assertForbidden();
    }
}
