<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AgendaRevisionLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaRevisionLinkTest extends TestCase
{
    use RefreshDatabase;

    private function revisionAgenda(): Agenda
    {
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();
        $agenda = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $agenda->fill([
            'title' => 'Agenda Perlu Revisi',
            'status' => 'needs_revision',
            'submitted_by' => $opdUser->id,
            'pic_phone' => '081234567890',
            'verification_note' => 'Lampirkan telaahan staf terbaru.',
        ])->save();

        return $agenda;
    }

    /** Ambil token dari link revisi di dalam teks pesan wa.me. */
    private function tokenFromWhatsappUrl(string $url): string
    {
        preg_match('#/revisi/([A-Za-z0-9]+)#', rawurldecode($url), $match);

        return $match[1];
    }

    private function revisionPayload(array $overrides = []): array
    {
        return array_merge([
            'agenda_type_id' => Agenda::query()->value('agenda_type_id'),
            'title' => 'Agenda Perlu Revisi (sudah diperbaiki)',
            'event_date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Aula Kantor Bupati',
            'description' => 'Deskripsi sudah diperbaiki.',
            'leader_target' => 'bupati',
            'pic_phone' => '081234567890',
        ], $overrides);
    }

    public function test_revision_link_works_once_then_expires(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $agenda = $this->revisionAgenda();

        $wa = $this->actingAs($admin)->postJson("/agendas/{$agenda->id}/whatsapp-status")->assertOk();
        $token = $this->tokenFromWhatsappUrl($wa->json('url'));
        auth()->logout();

        // Tamu (tanpa login) bisa membuka form berisi data lama + catatan revisi.
        $this->get("/revisi/{$token}")
            ->assertOk()
            ->assertSee('Lampirkan telaahan staf terbaru.')
            ->assertSee('Agenda Perlu Revisi');

        $this->put("/revisi/{$token}", $this->revisionPayload())
            ->assertRedirect("/revisi/{$token}");

        $agenda->refresh();
        $this->assertSame('submitted', $agenda->status);
        $this->assertSame('Agenda Perlu Revisi (sudah diperbaiki)', $agenda->title);
        $this->assertNotNull(AgendaRevisionLink::findByToken($token)->used_at);

        // Tepat setelah dikirim: konfirmasi. Dibuka lagi: link tidak berlaku.
        $this->get("/revisi/{$token}")->assertOk()->assertSee('Revisi terkirim');
        $this->get("/revisi/{$token}")->assertOk()->assertSee('Link revisi tidak berlaku');
        $this->put("/revisi/{$token}", $this->revisionPayload(['title' => 'Percobaan kedua']));
        $this->assertSame('Agenda Perlu Revisi (sudah diperbaiki)', $agenda->fresh()->title);
    }

    public function test_new_link_revokes_previous_link(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $agenda = $this->revisionAgenda();

        $first = $this->tokenFromWhatsappUrl($this->actingAs($admin)->postJson("/agendas/{$agenda->id}/whatsapp-status")->json('url'));
        $second = $this->tokenFromWhatsappUrl($this->actingAs($admin)->postJson("/agendas/{$agenda->id}/whatsapp-status")->json('url'));

        $this->assertNotSame($first, $second);
        $this->get("/revisi/{$first}")->assertSee('sudah diganti dengan link yang lebih baru');
        $this->get("/revisi/{$second}")->assertOk()->assertSee('Kirim revisi');
    }

    public function test_status_message_without_revision_link_for_approved_agenda(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $agenda = $this->revisionAgenda();
        $agenda->update(['status' => 'approved']);

        $response = $this->actingAs($admin)->postJson("/agendas/{$agenda->id}/whatsapp-status")->assertOk();

        $this->assertFalse($response->json('revision_link'));
        $this->assertStringNotContainsString('/revisi/', rawurldecode($response->json('url')));
        $this->assertSame(0, AgendaRevisionLink::query()->count());
    }

    public function test_unknown_token_shows_friendly_page(): void
    {
        $this->get('/revisi/token-tidak-ada')->assertOk()->assertSee('Link revisi tidak dikenali');
    }
}
