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

## Integrasi WhatsApp Blasting
AGPIM sekarang terintegrasi dengan paket `kstmostofa/laravel-whatsapp` untuk Cloud API + Web sidecar, plus modul internal blast di menu aplikasi.

### 1. Instalasi package
```bash
composer require kstmostofa/laravel-whatsapp livewire/livewire livewire/flux
php artisan vendor:publish --tag=laravel-whatsapp-config
php artisan vendor:publish --tag=laravel-whatsapp-migrations
php artisan migrate
```

### 2. Konfigurasi env
Isi variabel WhatsApp di `.env`:
- `WHATSAPP_ACCESS_TOKEN`
- `WHATSAPP_PHONE_NUMBER_ID`
- `WHATSAPP_BUSINESS_ACCOUNT_ID`
- `WHATSAPP_APP_SECRET`
- `WHATSAPP_VERIFY_TOKEN`
- `WHATSAPP_WEB_TOKEN` (wajib jika pakai web sidecar)

### 3. Jalankan service pendukung
```bash
php artisan queue:work
```

Jika memakai Web sidecar:
```bash
php artisan whatsapp:sidecar:install
php artisan whatsapp:sidecar:start
php artisan whatsapp:web:listen main
```

### 4. Akses fitur
- Panel package: `/whatsapp` (dibatasi middleware auth + role `super_admin`/`admin_prokopim`)
- Panel AGPIM blast: `/wa-blasts`

## Demo credential
- `superadmin@agpim.test`
- `prokopim@agpim.test`
- `bupati@agpim.test`
- `wabup@agpim.test`
- `sekda@agpim.test`
- `opd@agpim.test`
- `disdik@simpelsibang.test`

Password demo: `password`
