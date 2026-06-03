# AGPIM Production Blueprint

## 1. Analisis Kebutuhan
- **Aktor**: Super Admin, Admin Prokopim, Bupati, Wakil Bupati, Sekda, OPD.
- **Masalah inti**: pengajuan manual, bentrok jadwal, minim audit, sulit akses agenda pimpinan, dan dokumentasi kegiatan tersebar.
- **Tujuan**: satu platform terintegrasi untuk pengajuan, verifikasi, disposisi, dashboard agenda, tindak lanjut, notifikasi, audit trail, dan offline access.
- **Non-fungsional**: mobile first, responsif, toleran jaringan lambat, PWA/offline-first, aman, auditabel, dan mudah dipakai ASN/pimpinan usia 40-70 tahun.

## 2. Domain & Modul
1. Master Data (OPD, jenis kegiatan, role)
2. Pengajuan Agenda
3. Verifikasi Prokopim
4. Disposisi Pimpinan
5. Dashboard Agenda Pimpinan
6. Agenda Publik Internal
7. Tindak Lanjut & Dokumentasi
8. Notifikasi
9. Audit Trail
10. Offline Sync / PWA
11. Analitik Prokopim

## 3. ERD Ringkas
- `opds` 1..* `users`
- `opds` 1..* `agendas`
- `agenda_types` 1..* `agendas`
- `users` 1..* `agendas` (submitted_by / reviewed_by)
- `agendas` 1..* `agenda_documents`
- `agendas` 1..* `agenda_verifications`
- `agendas` 1..* `agenda_dispositions`
- `agendas` 1..* `agenda_follow_ups`
- `agenda_follow_ups` 1..* `follow_up_attachments`
- `users` 1..* `app_notifications`
- `users` 1..* `audit_logs`
- `users` 1..* `sync_snapshots`

## 4. Struktur Database
- **users**: role, opd_id, title, phone, kredensial
- **opds**: identitas perangkat daerah
- **agenda_types**: master jenis kegiatan
- **agendas**: inti pengajuan, waktu, target pimpinan, status, dokumen utama
- **agenda_documents**: dokumen pendukung dan lampiran internal
- **agenda_verifications**: audit keputusan verifikasi
- **agenda_dispositions**: usulan/approval disposisi pimpinan
- **agenda_follow_ups**: ringkasan hasil, rencana tindak lanjut, catatan
- **follow_up_attachments**: foto/video/dokumen hasil kegiatan
- **app_notifications**: in-app/email/WA queue metadata
- **audit_logs**: jejak perubahan terstruktur
- **sync_snapshots**: snapshot data untuk mode offline

## 5. Struktur Folder Laravel
- `app/Enums`: enum role
- `app/Http/Controllers`: landing, dashboard, agenda, public agenda
- `app/Http/Middleware`: role middleware
- `app/Http/Requests`: form validation
- `app/Policies`: agenda policy
- `app/Events` + `app/Listeners`: audit & notifikasi berbasis event
- `app/Models`: domain model AGPIM
- `database/migrations`: skema inti AGPIM
- `database/seeders`: master data + demo data
- `resources/views`: landing, dashboard, pengajuan, agenda internal
- `public/manifest.json`, `public/sw.js`: PWA shell
- `docs/agpim-blueprint.md`: dokumentasi blueprint produksi

## 6. Role Permission Matrix
| Modul | Super Admin | Prokopim | Bupati | Wabup | Sekda | OPD |
|---|---|---|---|---|---|---|
| Kelola user/role | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Kelola master data | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Buat pengajuan | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Verifikasi | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Disposisi | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Dashboard pimpinan | ✅ | ✅ | ✅ | ✅ | ✅ | 🔎 terbatas |
| Agenda publik internal | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Dokumen internal | ✅ | ✅ | terbatas | terbatas | terbatas | milik sendiri |
| Audit trail | ✅ | ✅ | baca | baca | baca | ❌ |

## 7. Wireframe UI/UX
- **Landing**: hero, statistik, workflow, value proposition, daftar jenis agenda.
- **Dashboard Pimpinan**: card agenda berikutnya, 4 KPI, timeline, pending decision kuning, priority merah, quick tabs, bentrok agenda.
- **Daftar Agenda**: card list, badge prioritas, status, penyelenggara, target pimpinan.
- **Agenda Publik Internal**: kartu ringkas tanpa dokumen internal.
- **Detail Agenda**: informasi inti, deteksi bentrok, dokumen, tindak lanjut.

## 8. Flowchart Sistem
1. OPD buat draft → submit
2. Prokopim verifikasi → approve / revisi / reject
3. Jika approve → menunggu disposisi
4. Pimpinan/Prokopim tetapkan kehadiran
5. Agenda tampil di dashboard dan agenda publik internal
6. Kegiatan selesai → unggah hasil + tindak lanjut
7. Audit trail dan notifikasi tercatat di tiap transisi

## 9. Sequence Diagram Ringkas
- **Submission**: OPD → AgendaController → AgendaPolicy → Agenda model → AgendaSubmitted event → Audit listener + Notification listener
- **Dashboard**: User → DashboardController → Agenda query → conflict detection → Blade view
- **Offline Sync**: Browser → Service Worker cache → fallback cache → reconnect → update `localStorage`/snapshot metadata

## 10. API Design
- `GET /` landing
- `GET /dashboard?leader=bupati`
- `GET /agendas`
- `POST /agendas`
- `GET /agendas/{agenda}`
- `GET /agenda-internal`

### API roadmap berikutnya
- `GET /api/v1/dashboard`
- `GET /api/v1/agendas`
- `POST /api/v1/agendas`
- `PATCH /api/v1/agendas/{id}/verify`
- `PATCH /api/v1/agendas/{id}/disposition`
- `POST /api/v1/agendas/{id}/follow-ups`
- `GET /api/v1/notifications`
- `POST /api/v1/sync/snapshots`

## 11. Implementasi Laravel yang Disiapkan
- Migration inti AGPIM
- Seeder master data dan demo data
- Model relasional untuk agenda/disposisi/audit/notifikasi
- Controller untuk landing, dashboard, daftar agenda, agenda publik
- Middleware role
- Policy `AgendaPolicy`
- Request validation `StoreAgendaRequest`
- Event `AgendaSubmitted`
- Listener `StoreAgendaAuditTrail`, `QueueAgendaNotification`

## 12. PWA Offline Architecture
- **Shell cache**: `/`, `/dashboard`, `/agendas`, `/agenda-internal`, manifest
- **Offline UX**: banner merah + last sync
- **Local persistence**: `localStorage` untuk timestamp, tabel `sync_snapshots` untuk strategi sinkron sisi server
- **Next step**: IndexedDB per-user untuk timeline, agenda detail, dan pending upload media

## 13. Notifikasi Architecture
- Table `app_notifications` menampung payload, channels, schedule, sent/read timestamp
- Listener saat event submission memicu notifikasi ke Super Admin dan Prokopim
- Tahap lanjutan: queue worker + mail notification + WhatsApp gateway adapter

## 14. Audit Trail Architecture
- Semua transisi penting dibungkus event/listener
- `audit_logs` menyimpan actor, object, before/after, metadata, ip/user agent
- Dapat diperluas ke observer untuk update/review/disposition/follow-up

## 15. Roadmap
### v1.0
- Pengajuan agenda, verifikasi, disposisi, dashboard pimpinan, agenda publik internal, audit log dasar, PWA shell

### v2.0
- Auth lengkap, upload file nyata, notifikasi email/WA, analytics Prokopim, policy granular, API mobile/PWA

### v3.0
- Offline sync dua arah, approval mobile, rekomendasi konflik cerdas, tanda tangan elektronik, integrasi SSO Pemda
