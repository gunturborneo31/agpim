<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWaBlastRequest;
use App\Http\Requests\UpdateWaBlastSettingRequest;
use App\Jobs\DispatchWaBlastJob;
use App\Models\User;
use App\Models\WaBlast;
use App\Models\WaBlastSetting;
use App\Support\WaPhoneFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WaBlastController extends Controller
{
    public function index(): View
    {
        $settings = WaBlastSetting::current();

        return view('whatsapp.blasts.index', [
            'settings' => $settings,
            'blasts' => WaBlast::query()
                ->with('creator')
                ->latest()
                ->limit(20)
                ->get(),
            'eligibleUsersCount' => User::query()
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->count(),
            'credentials' => [
                'access_token' => filled(config('laravel-whatsapp.access_token')),
                'phone_number_id' => filled(config('laravel-whatsapp.phone_number_id')),
                'business_account_id' => filled(config('laravel-whatsapp.business_account_id')),
                'verify_token' => filled(config('laravel-whatsapp.webhook.verify_token')),
                'web_token' => filled(config('laravel-whatsapp.web.token')),
            ],
            'webStatusCommand' => 'php artisan whatsapp:sidecar:status',
            'listenCommand' => 'php artisan whatsapp:web:listen main',
        ]);
    }

    public function updateSettings(UpdateWaBlastSettingRequest $request): RedirectResponse
    {
        $settings = WaBlastSetting::current();

        $settings->update([
            'cloud_enabled' => $request->boolean('cloud_enabled'),
            'web_enabled' => $request->boolean('web_enabled'),
            'default_backend' => $request->string('default_backend')->toString(),
            'web_session_name' => $request->string('web_session_name')->toString(),
            'send_delay_seconds' => $request->integer('send_delay_seconds'),
            'message_footer' => $request->string('message_footer')->toString() ?: null,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('wa-blasts.index')
            ->with('status', 'Pengaturan WhatsApp berhasil diperbarui.');
    }

    public function store(StoreWaBlastRequest $request): RedirectResponse
    {
        $recipientUsers = $this->buildRecipients($request->validated());

        if ($recipientUsers->isEmpty()) {
            return redirect()
                ->route('wa-blasts.index')
                ->withErrors(['blast' => 'Tidak ada penerima valid. Pastikan user memiliki nomor telepon.']);
        }

        $blast = WaBlast::query()->create([
            'title' => $request->string('title')->toString(),
            'message' => $request->string('message')->toString(),
            'backend' => $request->string('backend')->toString(),
            'target' => $request->string('target')->toString(),
            'target_role' => $request->string('target_role')->toString() ?: null,
            'status' => 'queued',
            'total_recipients' => $recipientUsers->count(),
            'created_by' => $request->user()?->id,
        ]);

        $rows = $recipientUsers->map(function (User $user) use ($blast): array {
            return [
                'wa_blast_id' => $blast->id,
                'user_id' => $user->id,
                'recipient_name' => $user->name,
                'raw_phone' => (string) $user->phone,
                'e164_phone' => WaPhoneFormatter::toE164((string) $user->phone),
                'web_jid' => WaPhoneFormatter::toWebJid((string) $user->phone),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->values()->all();

        foreach (array_chunk($rows, 250) as $chunk) {
            $blast->recipients()->insert($chunk);
        }

        DispatchWaBlastJob::dispatch($blast->id);

        return redirect()
            ->route('wa-blasts.index')
            ->with('status', 'Blast WA dijadwalkan. Proses pengiriman berjalan melalui queue.');
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function buildRecipients(array $validated): Collection
    {
        $query = User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        if (($validated['target'] ?? null) === 'role' && ! empty($validated['target_role'])) {
            $query->where('role', $validated['target_role']);
        }

        return $query->orderBy('name')->get()->filter(function (User $user): bool {
            return WaPhoneFormatter::toE164((string) $user->phone) !== null;
        })->unique(fn (User $user) => WaPhoneFormatter::toDigits((string) $user->phone))->values();
    }
}
