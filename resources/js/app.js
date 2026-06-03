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
});
