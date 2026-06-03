# AGPIM

AGPIM (Agenda Pimpinan) adalah fondasi aplikasi Laravel 12 untuk pengajuan undangan kegiatan OPD, verifikasi Prokopim, disposisi pimpinan, dashboard agenda, audit trail, notifikasi, dan PWA offline-ready.

## Stack
- Laravel 12
- Blade + TailwindCSS + AlpineJS
- SQLite/MySQL compatible schema
- Progressive Web App shell (`public/manifest.json`, `public/sw.js`)

## Modul yang sudah disiapkan
- Landing page AGPIM
- Dashboard pimpinan berbasis card/timeline
- Domain model agenda, disposisi, verifikasi, dokumen, tindak lanjut, audit, notifikasi, sync snapshot
- Seeder demo AGPIM
- Middleware role, request validation, policy, event, listener
- Blueprint produksi di `docs/agpim-blueprint.md`

## Menjalankan proyek
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

## Demo credential
- `superadmin@agpim.test`
- `prokopim@agpim.test`
- `bupati@agpim.test`
- `wabup@agpim.test`
- `sekda@agpim.test`
- `opd@agpim.test`

Password demo: `password`
