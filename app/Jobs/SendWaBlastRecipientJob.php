<?php

namespace App\Jobs;

use App\Models\WaBlast;
use App\Models\WaBlastRecipient;
use App\Models\WaBlastSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Kstmostofa\LaravelWhatsApp\Facades\WhatsApp;
use Throwable;

class SendWaBlastRecipientJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly int $blastId,
        public readonly int $recipientId,
    ) {
    }

    public function handle(): void
    {
        $blast = WaBlast::query()->find($this->blastId);
        $recipient = WaBlastRecipient::query()->find($this->recipientId);

        if (! $blast || ! $recipient || $recipient->status !== 'pending') {
            return;
        }

        $settings = WaBlastSetting::current();
        $message = trim($blast->message."\n\n".($settings->message_footer ?? ''));

        try {
            $this->sendMessage($blast, $recipient, $settings, $message);

            $recipient->update([
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => now(),
            ]);
        } catch (Throwable $throwable) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => mb_substr($throwable->getMessage(), 0, 1000),
                'sent_at' => now(),
            ]);
        }

        $this->syncBlastCounters($blast);
    }

    private function sendMessage(WaBlast $blast, WaBlastRecipient $recipient, WaBlastSetting $settings, string $message): void
    {
        $backend = $blast->backend;

        if ($backend === 'auto') {
            if ($settings->default_backend === 'web' && $settings->web_enabled) {
                $backend = 'web';
            } elseif ($settings->cloud_enabled) {
                $backend = 'cloud';
            } elseif ($settings->web_enabled) {
                $backend = 'web';
            } else {
                $backend = 'cloud';
            }
        }

        if ($backend === 'web') {
            if (! $recipient->web_jid) {
                throw new \RuntimeException('Nomor WA tidak valid untuk mode Web sidecar.');
            }

            WhatsApp::web($settings->web_session_name)->messages()->sendText($recipient->web_jid, $message);

            return;
        }

        if (! $recipient->e164_phone) {
            throw new \RuntimeException('Nomor WA tidak valid untuk mode Cloud API.');
        }

        WhatsApp::send($recipient->e164_phone, $message);
    }

    private function syncBlastCounters(WaBlast $blast): void
    {
        $freshBlast = WaBlast::query()->withCount([
            'recipients as pending_count' => fn ($query) => $query->where('status', 'pending'),
            'recipients as success_count_tmp' => fn ($query) => $query->where('status', 'sent'),
            'recipients as failed_count_tmp' => fn ($query) => $query->where('status', 'failed'),
        ])->find($blast->id);

        if (! $freshBlast) {
            return;
        }

        $status = 'processing';
        $sentAt = null;

        if ((int) $freshBlast->pending_count === 0) {
            $status = (int) $freshBlast->success_count_tmp > 0 ? 'completed' : 'failed';
            $sentAt = now();
        }

        $freshBlast->update([
            'status' => $status,
            'success_count' => (int) $freshBlast->success_count_tmp,
            'failed_count' => (int) $freshBlast->failed_count_tmp,
            'sent_at' => $sentAt,
        ]);
    }
}
