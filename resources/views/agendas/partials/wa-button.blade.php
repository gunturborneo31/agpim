{{-- Tombol kirim status pengajuan ke WhatsApp pengaju (wa.me). Variabel: $agenda, $compact (bool) --}}
<button type="button"
        x-data="waButton(@js(route('agendas.whatsapp-status', $agenda)))"
        @click.stop="send()"
        :disabled="sending"
        class="btn-primary {{ ($compact ?? false) ? '!px-2.5 !py-1 !text-xs' : '!px-3 !py-1.5 !text-xs' }}"
        style="background: linear-gradient(135deg, #1fa855 0%, #6fd49a 100%)"
        title="{{ $agenda->status === 'needs_revision' ? 'Kirim status + link revisi ke WhatsApp pengaju' : 'Kirim status pengajuan ke WhatsApp pengaju' }}">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.4-.3Z"/></svg>
    <span x-text="sending ? 'Menyiapkan…' : 'WhatsApp'">WhatsApp</span>
</button>
