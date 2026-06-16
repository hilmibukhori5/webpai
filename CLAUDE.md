# CLAUDE.md

## Ringkasan Project
Sistem Penyetaraan Modul PAI — aplikasi web untuk mahasiswa S1 Ilmu Aktuaria / S1 Matematika
(PSIA UB) yang ingin menyetarakan matkul yang sudah lulus ke Modul PAI level ASAI (A10–A70).
Mahasiswa mengajukan penyetaraan per modul berdasarkan eligibility otomatis (dihitung dari
nilai), lalu admin menyetujui/menolak tiap pengajuan dengan notifikasi email.

**Stack:** Laravel 12 (PHP 8.2+) · Blade + Tailwind · Eloquent/SQLite (dev) · Mailable + queue
(database driver) · maatwebsite/excel (import nilai, terpasang sejak Fase 2) · PHPUnit.

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
  — jangan ulang asumsi lama itu.
- `pai_modules.code` tetap A10–A70 (dipakai UI/desain). `official_code` (CF1-CF4/TA1-TA3) cuma
  referensi nama resmi ASAI, bukan identifier utama.

## Checklist Fase (lihat detail prompt tiap fase di `docs/spec.md` bagian 8)
- [x] Fase 0 — Setup & konvensi (Breeze Blade, role admin/student, middleware)
- [x] Fase 1 — Schema, model, seeder master (pai_modules, courses, module_course, skala nilai)
- [x] Fase 2 — Import nilai (Excel/CSV) + hitung `course_thresholds` (percentile per modul)
- [ ] Fase 3 — EligibilityService (INTI) + unit test
- [ ] Fase 4 — Sisi mahasiswa (register/login, dashboard modul, ajukan penyetaraan)
- [ ] Fase 5 — Sisi admin (dashboard per mahasiswa, detail, setujui/tolak per modul)
- [ ] Fase 6 — Email (ApprovedModule, RejectedModule, queue)
- [ ] Fase 7 — Polish & demo (seeder demo, validasi, README, test hijau semua)
