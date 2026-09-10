import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
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

    const statusClassMap = {
        visual_status_ak: {
            Belum: 'border-rose-500 bg-rose-200 text-rose-900',
            menunggu: 'border-amber-500 bg-amber-200 text-amber-900',
            'sedang dikerjakan': 'border-sky-500 bg-sky-200 text-sky-900',
            selesai: 'border-emerald-500 bg-emerald-200 text-emerald-900',
        },
        video_status_ak: {
            Belum: 'border-rose-500 bg-rose-200 text-rose-900',
            menunggu: 'border-amber-500 bg-amber-200 text-amber-900',
            'sedang dikerjakan': 'border-sky-500 bg-sky-200 text-sky-900',
            selesai: 'border-emerald-500 bg-emerald-200 text-emerald-900',
        },
        visual_status_prokopim: {
            'belum ada bahan': 'border-slate-500 bg-slate-200 text-slate-900',
            'sudah ada bahan dokumentasi': 'border-emerald-500 bg-emerald-200 text-emerald-900',
            'belum diperiksa': 'border-blue-500 bg-blue-200 text-blue-900',
            'perlu revisi': 'border-amber-500 bg-amber-200 text-amber-900',
            'ok tayang': 'border-emerald-500 bg-emerald-200 text-emerald-900',
        },
        video_status_prokopim: {
            'belum ada bahan': 'border-slate-500 bg-slate-200 text-slate-900',
            'sudah ada bahan dokumentasi': 'border-emerald-500 bg-emerald-200 text-emerald-900',
            'belum diperiksa': 'border-blue-500 bg-blue-200 text-blue-900',
            'perlu revisi': 'border-amber-500 bg-amber-200 text-amber-900',
            'ok tayang': 'border-emerald-500 bg-emerald-200 text-emerald-900',
        },
    };

    const submitStatusForm = async (form) => {
        const select = form.querySelector('select[data-status-name]');
        if (!select) {
            return;
        }

        const statusName = select.dataset.statusName;
        const formData = new FormData(form);
        const statusValue = formData.get(statusName);
        const cell = form.closest('td');

        if (!cell || !statusName || !statusValue) {
            return;
        }

        select.disabled = true;

        try {
            await axios.post(form.action, formData);

            const statusClasses = statusClassMap[statusName] || {};
            const classValue = statusClasses[statusValue] ?? 'border-slate-500 bg-slate-200 text-slate-900';
            cell.className = `px-3 py-4 ${classValue}`;
        } catch (error) {
            console.error('Status update failed', error);
        } finally {
            select.disabled = false;
        }
    };

    document.body.addEventListener('change', (event) => {
        const select = event.target;
        if (!(select instanceof HTMLSelectElement) || !select.dataset.statusSelect) {
            return;
        }

        const form = select.closest('form[data-status-form]');
        if (!form) {
            return;
        }

        submitStatusForm(form);
    });

    document.body.addEventListener('submit', async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.statusForm) {
            return;
        }

        event.preventDefault();
        await submitStatusForm(form);
    });
});
