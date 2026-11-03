@props(['excludeId' => null, 'initial' => false])

{{-- Peringatan bentrok jadwal. Membaca isian tanggal/jam/pejabat dari form tempat komponen ini berada. --}}
<div x-data="scheduleCheck({ endpoint: @js(route('schedule.check')), exclude: @js($excludeId), initial: @js((bool) $initial) })"
     x-cloak x-show="items.length > 0" x-transition.opacity
     {{ $attributes->class(['rounded-lg px-3.5 py-3 text-sm']) }}
     style="background: var(--grad-wait)" role="status" aria-live="polite">
    <p class="flex items-start gap-1.5 font-medium text-[#7a4a05]">
        <span class="material-symbols-outlined text-[18px]">event_busy</span>
        <span x-text="headline"></span>
    </p>
    <ul class="mt-1.5 space-y-0.5 pl-6 text-[13px] text-[#5c3a07]">
        <template x-for="(item, i) in items" :key="i">
            <li>
                <span class="font-mono" x-text="item.time"></span>
                <span x-text="' · ' + item.leader"></span>
                <span x-show="item.title" x-text="' — ' + item.title"></span>
            </li>
        </template>
    </ul>
    <p class="mt-1.5 pl-6 text-xs text-[#7a4a05]">Pengajuan tetap bisa dikirim. Prokopim yang akan menentukan jadwal akhirnya.</p>
</div>
