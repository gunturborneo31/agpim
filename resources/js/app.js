import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

const previewKind = (ext) => {
    if (ext === 'pdf') return 'pdf';
    if (ext === 'docx') return 'docx';
    if (IMAGE_EXTS.includes(ext)) return 'image';
    return 'other';
};

/**
 * Render halaman PDF ke <canvas> di dalam `box` (jalan juga di HP, tanpa PDF viewer browser).
 * pdf.js baru diunduh saat fungsi ini pertama kali dipanggil.
 */
async function renderPdfPages(box, data, maxPages = 3) {
    // pdf.js 4 memakai Promise.withResolvers (belum ada di browser HP versi lama).
    if (typeof Promise.withResolvers !== 'function') {
        Promise.withResolvers = function () {
            let resolve;
            let reject;
            const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
            return { promise, resolve, reject };
        };
    }

    // Worker dimuat sebagai modul biasa di halaman ini (globalThis.pdfjsWorker), bukan
    // sebagai Web Worker terpisah. Web Worker dari alamat lain (mis. Vite dev server)
    // diblokir browser, sehingga preview PDF gagal.
    const [pdfjs] = await Promise.all([
        import('pdfjs-dist'),
        import('pdfjs-dist/build/pdf.worker.min.mjs'),
    ]);

    // Pakai salinan data supaya ArrayBuffer asli tetap bisa dipakai ulang.
    const doc = await pdfjs.getDocument({ data: new Uint8Array(data.slice(0)) }).promise;
    const shown = Math.min(doc.numPages, maxPages);
    const targetWidth = Math.max(280, box.clientWidth || 640);
    const ratio = window.devicePixelRatio || 1;

    box.innerHTML = '';
    for (let i = 1; i <= shown; i++) {
        const page = await doc.getPage(i);
        const base = page.getViewport({ scale: 1 });
        const viewport = page.getViewport({ scale: (Math.min(targetWidth, 900) / base.width) * ratio });
        const canvas = document.createElement('canvas');
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        canvas.className = 'mx-auto block w-full max-w-[900px] rounded-md border border-line bg-white';
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        box.appendChild(canvas);
    }

    return { pageCount: doc.numPages, pagesShown: shown };
}

/** Ubah DOCX menjadi HTML sederhana. mammoth baru diunduh saat dibutuhkan. */
async function renderDocxHtml(arrayBuffer) {
    const { default: mammoth } = await import('mammoth/mammoth.browser.js');
    const result = await mammoth.convertToHtml({ arrayBuffer });
    return result.value || '<p>Dokumen kosong.</p>';
}

// Input file dengan pratinjau (dipakai oleh <x-ui.file-input>).
Alpine.data('filePreview', (maxMb = 5) => ({
    file: null,
    url: null,
    kind: null, // 'pdf' | 'docx' | 'other'
    ext: '',
    html: '',
    error: '',
    loading: false,
    dragging: false,
    pageCount: 0,
    pagesShown: 0,

    get sizeLabel() {
        if (!this.file) return '';
        const kb = this.file.size / 1024;
        return kb < 1024 ? `${Math.max(1, Math.round(kb))} KB` : `${(kb / 1024).toFixed(1)} MB`;
    },

    async pick(event) {
        const file = event.target.files?.[0];
        this.revoke();
        this.error = '';
        this.html = '';
        if (this.$refs.pdfPages) this.$refs.pdfPages.innerHTML = '';

        if (!file) {
            this.file = null;
            return;
        }

        if (file.size > maxMb * 1024 * 1024) {
            this.error = `Ukuran file ${(file.size / 1048576).toFixed(1)} MB melebihi batas ${maxMb} MB. Pilih file yang lebih kecil.`;
            event.target.value = '';
            this.file = null;
            return;
        }

        this.file = file;
        this.ext = (file.name.split('.').pop() || '').toLowerCase();
        this.kind = this.ext === 'pdf' ? 'pdf' : this.ext === 'docx' ? 'docx' : 'other';
        this.url = URL.createObjectURL(file);

        if (this.kind === 'other') return;

        this.loading = true;
        try {
            const data = await file.arrayBuffer();
            if (this.kind === 'pdf') {
                await this.$nextTick();
                Object.assign(this, await renderPdfPages(this.$refs.pdfPages, data, 3));
            } else {
                this.html = await renderDocxHtml(data);
            }
        } catch (e) {
            console.error('Preview failed', e);
            this.kind = 'other';
        } finally {
            this.loading = false;
        }
    },

    clear() {
        if (this.$refs.pdfPages) this.$refs.pdfPages.innerHTML = '';
        this.revoke();
        this.file = null;
        this.kind = null;
        this.html = '';
        this.error = '';
        if (this.$refs.input) this.$refs.input.value = '';
    },

    revoke() {
        if (this.url) URL.revokeObjectURL(this.url);
        this.url = null;
    },

    destroy() {
        this.revoke();
    },
}));

// Pratinjau dokumen yang sudah tersimpan (dipakai popup agenda).
// items: [{ label, url, ext, name }]
Alpine.data('docViewer', (items = []) => ({
    items,
    index: 0,
    loading: false,
    failed: false,
    html: '',
    pageCount: 0,
    pagesShown: 0,
    renderedFor: null,

    get current() {
        return this.items[this.index] || null;
    },

    get kind() {
        return this.current ? previewKind(this.current.ext) : null;
    },

    select(i) {
        this.index = i;
        this.render();
    },

    async render() {
        const item = this.current;
        if (!item || this.renderedFor === item.url) return;

        this.renderedFor = item.url;
        this.failed = false;
        this.html = '';
        this.pageCount = 0;
        this.pagesShown = 0;
        if (this.$refs.pages) this.$refs.pages.innerHTML = '';

        if (this.kind !== 'pdf' && this.kind !== 'docx') return;

        this.loading = true;
        try {
            const response = await fetch(item.url, { credentials: 'same-origin' });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.arrayBuffer();
            if (this.renderedFor !== item.url) return; // pindah tab saat masih memuat

            if (this.kind === 'pdf') {
                this.loading = false;
                await this.$nextTick();
                Object.assign(this, await renderPdfPages(this.$refs.pages, data, 10));
            } else {
                this.html = await renderDocxHtml(data);
            }
        } catch (e) {
            console.error('Document preview failed', e);
            this.failed = true;
        } finally {
            this.loading = false;
        }
    },
}));

// Notifikasi singkat (toast). Panggil: window.toast('Pesan', 'success' | 'error').
Alpine.store('toasts', {
    items: [],
    push(message, type = 'success') {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => this.dismiss(id), type === 'error' ? 7000 : 4500);
    },
    dismiss(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
});
window.toast = (message, type) => Alpine.store('toasts').push(message, type);

/**
 * Minta pesan WhatsApp terbaru dari server (status "Perlu revisi" sekaligus membuat
 * link revisi sekali pakai), lalu buka wa.me di tab baru.
 */
async function openWhatsappStatus(endpoint) {
    // Tab dibuka lebih dulu (masih dalam aksi klik) supaya tidak diblokir browser.
    const tab = window.open('', '_blank');

    try {
        const { data } = await window.axios.post(endpoint, {}, { headers: { Accept: 'application/json' } });
        if (tab) {
            tab.location.href = data.url;
        } else {
            window.location.href = data.url;
        }
        window.toast(
            data.revision_link
                ? 'WhatsApp dibuka dengan link revisi baru. Link yang pernah dikirim sebelumnya sudah tidak berlaku.'
                : 'WhatsApp dibuka. Tekan kirim di WhatsApp untuk mengirim status.',
            'success',
        );
    } catch (error) {
        tab?.close();
        window.toast(error.response?.data?.message || 'Pesan WhatsApp gagal disiapkan. Coba lagi.', 'error');
    }
}

// Tombol WhatsApp mandiri (kolom Aksi tabel dan kartu HP).
Alpine.data('waButton', (endpoint) => ({
    sending: false,
    async send() {
        if (this.sending) return;
        this.sending = true;
        await openWhatsappStatus(endpoint);
        this.sending = false;
    },
}));

/**
 * Notifikasi otomatis usulan masuk di halaman Agenda admin (polling, tanpa refresh).
 * Bisa dimatikan; pilihan disimpan di browser.
 */
Alpine.data('incomingWatcher', ({ endpoint, since, interval = 20000 }) => ({
    enabled: true,
    since,
    newItems: [],
    seen: new Set(),
    timer: null,
    storageKey: 'agpim:incoming-notify',

    init() {
        try {
            this.enabled = localStorage.getItem(this.storageKey) !== 'off';
        } catch (e) {
            this.enabled = true;
        }
        if (this.enabled) this.start();

        document.addEventListener('visibilitychange', () => {
            if (this.enabled && !document.hidden) this.check();
        });
    },

    destroy() {
        this.stop();
    },

    toggle() {
        this.enabled = !this.enabled;
        try {
            localStorage.setItem(this.storageKey, this.enabled ? 'on' : 'off');
        } catch (e) {
            // tidak masalah bila penyimpanan browser tidak tersedia
        }

        if (this.enabled) {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().catch(() => {});
            }
            this.start();
            window.toast('Notifikasi usulan masuk aktif.', 'success');
        } else {
            this.stop();
            window.toast('Notifikasi usulan masuk dimatikan.', 'success');
        }
    },

    start() {
        this.stop();
        this.timer = setInterval(() => this.check(), interval);
    },

    stop() {
        if (this.timer) clearInterval(this.timer);
        this.timer = null;
    },

    async check() {
        try {
            const { data } = await window.axios.get(endpoint, { params: { since: this.since } });
            this.since = data.now;
            this.updateCounters(data.pending);

            const fresh = (data.items || []).filter((item) => !this.seen.has(`${item.id}:${item.submitted_at}`));
            if (fresh.length === 0) return;

            fresh.forEach((item) => this.seen.add(`${item.id}:${item.submitted_at}`));
            this.newItems.push(...fresh);
            this.announce(fresh);
        } catch (e) {
            // Gagal sesaat (offline / sesi habis) tidak perlu mengganggu; coba lagi di putaran berikutnya.
        }
    },

    announce(items) {
        const first = items[0];
        const message = items.length === 1
            ? `Usulan baru: ${first.title} (${first.opd})`
            : `${items.length} usulan baru masuk, termasuk "${first.title}"`;

        window.toast(message, 'success');
        this.beep();

        if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification('AGPIM · Usulan agenda baru', { body: message, icon: '/icon-192x192.png', tag: 'agpim-incoming' });
            notification.onclick = () => { window.focus(); notification.close(); };
        }
    },

    // Bunyi singkat dua nada (tanpa file audio).
    beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [880, 1175].forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.0001, ctx.currentTime + i * 0.18);
                gain.gain.exponentialRampToValueAtTime(0.15, ctx.currentTime + i * 0.18 + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + i * 0.18 + 0.16);
                osc.connect(gain).connect(ctx.destination);
                osc.start(ctx.currentTime + i * 0.18);
                osc.stop(ctx.currentTime + i * 0.18 + 0.17);
            });
        } catch (e) {
            // browser memblokir audio sebelum ada interaksi; abaikan
        }
    },

    // Perbarui angka "Perlu verifikasi" di tab dan badge menu.
    updateCounters(pending) {
        document.querySelectorAll('[data-pending-count]').forEach((el) => {
            el.textContent = pending > 99 ? '99+' : pending;
            el.hidden = el.dataset.hideZero !== undefined && pending === 0;
        });
    },
}));

/**
 * Peringatan bentrok jadwal di form pengajuan. Mengamati isian event_date, start_time,
 * end_time, time_unknown, dan leader_target pada form induknya. Tidak memblokir pengiriman.
 */
Alpine.data('scheduleCheck', ({ endpoint, exclude = null, initial = false }) => ({
    items: [],
    leaderFiltered: false,
    timeKnown: false,
    timer: null,
    lastQuery: '',

    init() {
        const form = this.$el.closest('form');
        if (!form) return;
        this.form = form;

        const watched = ['event_date', 'start_time', 'end_time', 'time_unknown', 'leader_target'];
        const onChange = (event) => {
            if (!watched.includes(event.target?.name)) return;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.check(), 400);
        };
        form.addEventListener('change', onChange);
        form.addEventListener('input', onChange);

        if (initial) this.check();
    },

    get headline() {
        const n = this.items.length;
        const who = this.leaderFiltered ? 'pejabat yang sama' : 'pimpinan';
        return this.timeKnown
            ? `Jam ini sudah ada ${n} agenda untuk ${who}:`
            : `Tanggal ini sudah ada ${n} agenda untuk ${who}:`;
    },

    value(name) {
        const field = this.form.elements.namedItem(name);
        if (!field) return '';
        if (field instanceof RadioNodeList) return field.value;
        if (field.type === 'checkbox') return field.checked ? '1' : '';
        return field.value;
    },

    async check() {
        const date = this.value('event_date');
        if (!date) {
            this.items = [];
            return;
        }

        const params = {
            date,
            start: this.value('start_time') || undefined,
            end: this.value('end_time') || undefined,
            time_unknown: this.value('time_unknown') ? 1 : 0,
            leader: this.value('leader_target') || undefined,
            exclude: exclude || undefined,
        };
        const query = JSON.stringify(params);
        if (query === this.lastQuery) return;
        this.lastQuery = query;

        try {
            const { data } = await window.axios.get(endpoint, { params });
            if (query !== this.lastQuery) return; // isian sudah berubah lagi
            this.items = data.items || [];
            this.leaderFiltered = data.leader_filtered;
            this.timeKnown = data.time_known;
        } catch (e) {
            this.items = []; // gagal cek tidak boleh mengganggu pengisian form
        }
    },
}));

/**
 * Kalender Dashboard: kalender kecil, popup per tanggal, dan kalender penuh bergaya
 * Google Calendar. Ganti bulan lewat AJAX tanpa memuat ulang halaman.
 */
Alpine.data('dashboardCalendar', ({ map, year, month, label, baseUrl, leader, today }) => ({
    calendarMap: map,
    calendarYear: year,
    calendarMonth: month, // 1-12
    calendarLabel: label,
    calendarLoading: false,
    calendarModal: false,
    fullCalendar: false,
    selectedDate: null,
    selectedItems: [],
    selectedLabel: '',
    today,
    weekdays: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],

    openDate(date, dateLabel) {
        this.selectedDate = date;
        this.selectedLabel = dateLabel || this.dateLabel(date);
        this.selectedItems = (this.calendarMap[date] || []).slice();
        this.calendarModal = true;
    },

    dateLabel(date) {
        const [y, m, d] = date.split('-').map(Number);
        return new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
            .format(new Date(y, m - 1, d));
    },

    monthUrl(delta) {
        const target = new Date(this.calendarYear, this.calendarMonth - 1 + delta, 1);
        const params = new URLSearchParams({ leader, calendar_month: target.getMonth() + 1, calendar_year: target.getFullYear() });
        return `${baseUrl}?${params}`;
    },

    // Grid 6 minggu (Senin–Minggu) untuk kalender penuh.
    get fullDays() {
        const first = new Date(this.calendarYear, this.calendarMonth - 1, 1);
        const offset = (first.getDay() + 6) % 7; // Senin = 0
        const days = [];
        for (let i = 0; i < 42; i++) {
            const d = new Date(this.calendarYear, this.calendarMonth - 1, 1 - offset + i);
            const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            days.push({
                key,
                day: d.getDate(),
                inMonth: d.getMonth() === this.calendarMonth - 1,
                isToday: key === this.today,
                isWeekend: i % 7 >= 5,
                items: this.calendarMap[key] || [],
            });
        }
        // Buang minggu terakhir bila seluruhnya di luar bulan ini.
        return days.slice(35).every((d) => !d.inMonth) ? days.slice(0, 35) : days;
    },

    chipClass(priority) {
        return { tinggi: 'soft-red text-[#9b2a1b]', sedang: 'soft-amber text-[#7a4a05]' }[priority] || 'soft-blue text-[#23377a]';
    },

    startTime(item) {
        return (item.time || '').split(' - ')[0];
    },

    async loadMonth(url) {
        if (this.calendarLoading) return;
        this.calendarLoading = true;
        try {
            const { data } = await window.axios.get(url, { headers: { Accept: 'application/json' } });
            this.calendarMap = data.map;
            this.calendarYear = data.year;
            this.calendarMonth = data.month;
            this.calendarLabel = data.label;
            document.getElementById('dashboard-calendar').innerHTML = data.html;
            history.replaceState(null, '', url);
        } catch (e) {
            window.toast('Kalender gagal dimuat. Periksa koneksi, lalu coba lagi.', 'error');
        } finally {
            this.calendarLoading = false;
        }
    },

    goToToday() {
        const [y, m] = this.today.split('-').map(Number);
        if (y === this.calendarYear && m === this.calendarMonth) return;
        this.loadMonth(this.monthUrl((y - this.calendarYear) * 12 + (m - this.calendarMonth)));
    },
}));

// Form keputusan verifikasi di popup agenda: simpan tanpa memuat ulang halaman.
Alpine.data('verifyForm', ({ action = '', attendanceSource = '', agendaId = null, waEndpoint = null, hasPhone = false } = {}) => ({
    action,
    attendanceSource,
    hasPhone,
    saving: false,
    saved: false,
    editing: false, // untuk agenda yang sudah diputuskan: form baru tampil setelah "Ubah keputusan"
    sendingWa: false,
    errors: {},

    async sendWhatsapp() {
        if (this.sendingWa || !waEndpoint) return;
        this.sendingWa = true;
        await openWhatsappStatus(waEndpoint);
        this.sendingWa = false;
    },

    err(field) {
        return (this.errors[field] || [])[0] || '';
    },

    async submit(form) {
        if (this.saving) return;
        this.saving = true;
        this.errors = {};

        try {
            // Pakai getAttribute: form ini punya input bernama "action", jadi form.action
            // mengembalikan input tersebut, bukan URL tujuan.
            const { data } = await window.axios.post(form.getAttribute('action'), new FormData(form), {
                headers: { Accept: 'application/json' },
            });

            this.saved = true;
            this.hasPhone = Boolean(data.whatsapp_number);

            // Perbarui label status di tabel, kartu HP, dan kepala popup.
            document.querySelectorAll(`[data-agenda-status="${agendaId}"]`).forEach((el) => {
                el.innerHTML = data.status_html;
            });

            window.toast(data.message || 'Keputusan tersimpan.', 'success');
        } catch (error) {
            const response = error.response;
            if (response?.status === 422) {
                this.errors = response.data?.errors || {};
                window.toast(response.data?.message || 'Keputusan belum lengkap. Periksa isian yang ditandai.', 'error');
            } else if (response?.status === 419) {
                window.toast('Sesi Anda berakhir. Muat ulang halaman, lalu simpan lagi.', 'error');
            } else if (response?.status === 403) {
                window.toast('Anda tidak punya izin untuk memverifikasi agenda ini.', 'error');
            } else {
                window.toast('Keputusan gagal disimpan. Periksa koneksi internet, lalu coba lagi.', 'error');
            }
        } finally {
            this.saving = false;
        }
    },
}));

Alpine.start();

const updateOfflineBanner = () => {
    const banner = document.getElementById('offline-banner');
    const label = document.getElementById('last-sync-label');

    if (!banner || !label) {
        return;
    }

    const lastSync = localStorage.getItem('agpim:last-sync') ?? new Date().toLocaleString('id-ID');
    label.textContent = lastSync;
    banner.classList.toggle('hidden', navigator.onLine);
};

window.addEventListener('online', () => {
    localStorage.setItem('agpim:last-sync', new Date().toLocaleString('id-ID'));
    updateOfflineBanner();
});

window.addEventListener('offline', updateOfflineBanner);
window.addEventListener('load', () => {
    localStorage.setItem('agpim:last-sync', localStorage.getItem('agpim:last-sync') ?? new Date().toLocaleString('id-ID'));
    updateOfflineBanner();

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
});
