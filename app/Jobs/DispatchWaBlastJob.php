<?php

namespace App\Jobs;

use App\Models\WaBlast;
use App\Models\WaBlastSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchWaBlastJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $blastId)
    {
    }

    public function handle(): void
    {
        $blast = WaBlast::query()->with('recipients')->find($this->blastId);
        if (! $blast || ! in_array($blast->status, ['queued', 'processing'], true)) {
            return;
        }

        $settings = WaBlastSetting::current();
        $delay = max(0, (int) $settings->send_delay_seconds);

        $blast->update(['status' => 'processing']);

        $pendingRecipients = $blast->recipients->where('status', 'pending')->values();
        if ($pendingRecipients->isEmpty()) {
            $blast->update(['status' => 'failed']);

            return;
        }

        foreach ($pendingRecipients as $index => $recipient) {
            SendWaBlastRecipientJob::dispatch($blast->id, $recipient->id)
                ->delay(now()->addSeconds($delay * $index));
        }
    }
}
