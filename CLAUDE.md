# CLAUDE.md

## Ringkasan Project
Sistem Penyetaraan Modul PAI — aplikasi web untuk mahasiswa S1 Ilmu Aktuaria / S1 Matematika
(Departemen Matematika UB) yang ingin menyetarakan matkul yang sudah lulus ke Modul PAI level ASAI (A10–A70).
Mahasiswa mengajukan penyetaraan per modul berdasarkan eligibility otomatis (dihitung dari
nilai), lalu admin menyetujui/menolak tiap pengajuan dengan notifikasi email.

**Stack:** Laravel 12 (PHP 8.2+) · Blade + Tailwind · Eloquent/MySQL (dev via XAMPP, db `webpai`)
· Mailable + queue (database driver, SMTP asli ke Gmail sejak Fase 6 — bukan MailHog/Mailpit) ·
maatwebsite/excel (import nilai, sejak Fase 2) · PHPUnit (test suite tetap pakai SQLite in-memory
+ MAIL_MAILER=array via `phpunit.xml`, independen dari `.env`, tidak pernah kirim email asli).

## Aturan Kerja
- **Selalu rujuk `docs/spec.md`** sebagai sumber kebenaran domain. Jangan menebak aturan bisnis.
- **Jangan ubah aturan eligibility (bagian 4)** atau **design system (bagian 10)** di
  `docs/spec.md` tanpa konfirmasi eksplisit dari user — itu kontrak yang sudah disepakati.
- **Kerjakan per fase**, sesuai urutan di bagian 8 `docs/spec.md`. Satu fase = satu sesi:
  rencana dulu → tunggu approve → implement → tunjukkan diff & hasil run (migrate/test) →
  commit → baru lanjut fase berikutnya. Jangan loncat fase atau gabung beberapa fase tanpa diminta.
- Fase 3 (Eligibility Service) **wajib** disertai unit test sebelum dianggap selesai.
- Kalau spec ambigu atau ada bagian "PERLU KONFIRMASI", tanya user, jangan asumsi sendiri.
  Contoh nyata: percentile awalnya diasumsikan 1 nilai global (`EQUIVALENCY_PERCENTILE`), user
  klarifikasi ternyata **per-modul** (kolom `pai_modules.percentile`, lihat bagian 2/4a spec)
  — jangan ulang asumsi lama itu. Decision tree 4c juga sudah dikonfirmasi user (lihat spec
  bagian 4c): PKS Lama cuma valid fallback kalau matched courses mengandung kode kurikulum
  lama; kalau semua kode baru, tetap `decision=none` walau lolos syarat PKS Lama matematis.
- `pai_modules.code` tetap A10–A70 (dipakai UI/desain). `official_code` (CF1-CF4/TA1-TA3) cuma
  referensi nama resmi ASAI, bukan identifier utama.
- Retake/duplikat `course_grades` (no_induk+course sama, >1 baris): pakai **NA tertinggi**
  (dikonfirmasi user, lihat spec bagian 4).
- Saat butuh urutan migration baru, hati-hati timestamp identik + urutan alfabetis bisa bikin
  FK gagal di MySQL walau lolos di SQLite (sudah kejadian sekali, lihat git log).
- **Test gotcha:** `User::factory()->create()` TANPA override `role` menghasilkan object in-memory
  dengan `role=null` (kolom itu cuma kena DB default saat INSERT, model-nya tidak di-refetch).
  `actingAs()` memakai object in-memory itu apa adanya, jadi `role:student`/`role:admin` middleware
  bisa 403 walau di DB rolenya sudah benar. Selalu `->fresh()` user hasil factory sebelum
  `actingAs()` kalau tidak override role secara eksplisit (atau akses lewat relasi seperti
  `$student->user`, yang otomatis fresh-query).
- Base `App\Http\Controllers\Controller` Laravel 12 itu kosong (tidak ada `AuthorizesRequests`
  bawaan kayak versi lama) — sudah ditambah trait itu di Fase 5 supaya `$this->authorize()` jalan.
  Login redirect (`AuthenticatedSessionController`) sudah role-aware (admin -> `admin.students.index`,
  student -> `dashboard`) sejak Fase 5 — jangan balikin ke `route('dashboard')` polos.
- **Mail asli**, bukan MailHog/Mailpit: `.env` pakai `MAIL_MAILER=smtp` ke Gmail (kredensial app
  password, JANGAN taruh di `.env.example` atau commit — `.env.example` cuma placeholder kosong).
  Mailable (`ApprovedModule`/`RejectedModule`) implements `ShouldQueue` jadi otomatis masuk
  `jobs` table, perlu `php artisan queue:work` (atau `queue:work --once` buat manual test) supaya
  benar-benar terkirim. Preview tanpa kirim asli: `/dev/mail-preview/approved` dan `/rejected`
  (local-only, ambil submission pertama dari DB).
- `DemoSeeder` (Fase 7) **tidak** ikut `DatabaseSeeder` default — jalankan manual lewat
  `php artisan db:seed --class=DemoSeeder` setelah master data ke-seed. Isinya 1 admin + 6
  mahasiswa yang masing-masing mengaktifkan 1 cabang decision tree persis (dipakai sebagai
  acceptance test informal — kalau cabangnya berubah karena edit EligibilityService, seeder
  ini akan throw RuntimeException karena hasil eligibility-nya tidak sesuai harapan lagi).
  Kredensial demo & detail skenario ada di README.
- **Laporan penyetaraan** (di luar 8 fase asli, ditambah belakangan atas permintaan user):
  `Admin\ReportController` + `App\Exports\EquivalencyReportExport` — download .xlsx PER SKEMA
  (`/admin/reports/export/{lama|baru}`), cuma submission **approved**, 1 baris = 1 submission
  (bukan 1 per mahasiswa). PKS Lama nilai = NH, PKS Baru nilai = NA. Header 2-baris + grup
  Kode/Nilai/Semester berulang dibangun manual via PhpSpreadsheet (`WithEvents`/`AfterSheet`,
  bukan `FromArray`/`WithHeadings` biasa — terlalu kompleks buat itu). No Induk WAJIB
  `setCellValueExplicit(..., DataType::TYPE_STRING)`, kalau tidak PhpSpreadsheet auto-detect
  NIM yang semua-digit jadi angka (resiko corrupt di Excel asli).
- Nav admin (`layouts/navigation.blade.php`) role-aware sejak fitur Laporan ditambah — sebelumnya
  brand-link & nav-link admin salah arah ke `route('dashboard')` (role:student-only, bakal 403
  buat admin). Kalau nambah halaman admin baru, tambahkan link di sini juga (desktop + mobile
  responsive nav, ada 2 blok terpisah).

## Checklist Fase (lihat detail prompt tiap fase di `docs/spec.md` bagian 8)
- [x] Fase 0 — Setup & konvensi (Breeze Blade, role admin/student, middleware)
- [x] Fase 1 — Schema, model, seeder master (pai_modules, courses, module_course, skala nilai)
- [x] Fase 2 — Import nilai (Excel/CSV) + hitung `course_thresholds` (percentile per modul)
- [x] Fase 3 — EligibilityService (INTI) + unit test
- [x] Fase 4 — Sisi mahasiswa (register/login, dashboard modul, ajukan penyetaraan)
- [x] Fase 5 — Sisi admin (dashboard per mahasiswa, detail, setujui/tolak per modul)
- [x] Fase 6 — Email (ApprovedModule, RejectedModule, queue)
- [x] Fase 7 — Polish & demo (seeder demo, validasi, README, test hijau semua)

Semua fase di bagian 8 spec sudah selesai.

## Design System (bagian 10)
Diterapkan ke **dashboard mahasiswa** (sesuai canned prompt section 10 — langkah 1-3):
- `tailwind.config.js`: font Inter (body) + Plus Jakarta Sans (heading, class `font-heading`),
  warna modul `module.a10`..`module.a70` (`bg-module-a10` dst).
- Komponen reusable di `resources/views/components/`: `<x-status-badge variant="...">`,
  `<x-module-card>`, `<x-metric-card>`, `<x-btn variant="primary|ghost|disabled">`.
  `status-badge` extend ke variant submission (`pending`/`approved`/`rejected`) selain 3 state
  eligibility asli di spec, biar bahasa warnanya konsisten di satu komponen.
- `layouts/navigation.blade.php`/`app.blade.php` diselaraskan warnanya (gray-* -> slate-*) biar
  nggak nyentrik sendiri dibanding dashboard yang baru.
- **Belum dikerjakan** (di luar scope canned prompt ini, tawarkan sebagai follow-up):
  admin UI (sidebar, drawer/modal detail — section 10 "Pola layout" nyebut ini tapi 3-step
  prompt-nya cuma minta dashboard mahasiswa), icon package proper (Lucide/Tabler — sementara
  inline SVG buat 2 ikon yang dipakai sekarang, check & lock), dark mode (opsional di spec).
- Verifikasi visual: sandbox ini nggak ada chromium-cli/Playwright (gagal install, perlu
  download browser binary) — diverifikasi via `npm run build` sukses + curl session login
  cek class HTML yang ke-render (`bg-module-a10`, `font-heading`, `rounded-2xl`, dst.) + semua
  test tetap hijau. Kalau perlu screenshot asli, jalankan `/run-skill-generator` dulu buat setup
  browser automation di project ini.
