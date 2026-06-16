# Sistem Penyetaraan Modul PAI

Aplikasi web untuk mahasiswa S1 Ilmu Aktuaria / S1 Matematika (Departemen Matematika UB) yang ingin
menyetarakan matkul yang sudah lulus ke Modul PAI level ASAI (A10–A70). Mahasiswa
mengajukan penyetaraan per modul berdasarkan eligibility otomatis (dihitung dari nilai),
lalu admin menyetujui/menolak tiap pengajuan dengan notifikasi email.

Aturan bisnis lengkap (skema PKS Lama/Baru, decision tree, harga, dll) ada di
[`docs/spec.md`](docs/spec.md) — itu sumber kebenaran domain untuk project ini.

## Stack

- Laravel 12 (PHP 8.2+), Blade + Tailwind (Laravel Breeze)
- MySQL/MariaDB (dev pakai XAMPP)
- `maatwebsite/excel` untuk import nilai
- Mail via SMTP (queue database driver)
- PHPUnit (test pakai SQLite in-memory, terisolasi dari `.env`)

## Setup

1. **Clone & install dependency**

   ```bash
   composer install
   npm install
   ```

2. **Environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Edit `.env`:
   - `DB_*` — sesuaikan dengan MySQL lokal kamu (default: `127.0.0.1:3306`, database `webpai`,
     user `root` tanpa password — cocok untuk XAMPP default). Buat database-nya dulu kalau
     belum ada:
     ```bash
     mysql -u root -e "CREATE DATABASE webpai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     ```
   - `MAIL_*` — isi kredensial SMTP (mis. Gmail dengan [App Password](https://myaccount.google.com/apppasswords),
     **bukan** password akun biasa). Kalau tidak mau kirim email asli saat develop, set
     `MAIL_MAILER=log` (email akan ditulis ke `storage/logs/laravel.log` saja).

3. **Migrate & seed master data** (modul PAI A10–A70, daftar matkul, pivot kurikulum)

   ```bash
   php artisan migrate --seed
   ```

4. **(Opsional) Seed data demo** — 1 admin + 6 mahasiswa contoh yang masing-masing
   mengaktifkan satu cabang decision tree (eligible baru, eligible lama, lolos lama tapi
   kode baru, belum lengkap, tepat di batas 3.5, dan satu yang sudah ditolak admin):

   ```bash
   php artisan db:seed --class=DemoSeeder
   ```

   **Kredensial demo** (password semua `password`):

   | Role | Email | Keterangan |
   |---|---|---|
   | Admin | `admin@pai.test` | dashboard admin |
   | Student | `ahmad@pai.test` | A10 — eligible PKS Baru, sudah disetujui |
   | Student | `siti@pai.test` | A30 — eligible PKS Lama, masih pending |
   | Student | `budi@pai.test` | A40 — lolos PKS Lama tapi kode kurikulum baru → belum eligible |
   | Student | `dewi@pai.test` | A60 — belum lengkap matkul komponennya |
   | Student | `rudi@pai.test` | A20 — rata-rata bobot pas 3.5 → gagal |
   | Student | `maya@pai.test` | A50 — eligible PKS Baru tapi ditolak admin (coba "ajukan ulang") |

5. **Build asset & jalankan**

   ```bash
   npm run build      # atau `npm run dev` untuk watch mode
   php artisan serve
   php artisan queue:work   # wajib jalan biar email ke-kirim (queue database driver)
   ```

## Cara import nilai

1. Login sebagai admin → buka **Import Nilai** (`/admin/grades/import`).
2. Pilih matkul + isi label semester (mis. `Genap 2223`).
3. Upload file Excel/CSV dengan kolom: `No Induk, Nama, NA, NH`. Contoh file bisa didownload
   langsung dari halaman import (`public/samples/course_grades_sample.csv`).
4. Baris yang nilainya tidak valid (NA di luar 0–100, NH tidak dikenal) otomatis di-skip dan
   dilaporkan — baris lain tetap masuk.
5. Setelah import, `course_thresholds` untuk matkul itu otomatis di-recompute (percentile
   diambil dari modul tempat matkul itu jadi komponen, bukan satu nilai global — lihat
   `docs/spec.md` bagian 4a).
6. Recompute manual (mis. setelah ubah data lewat cara lain) bisa lewat artisan:

   ```bash
   php artisan thresholds:recompute            # semua course
   php artisan thresholds:recompute MAA62043    # 1 course by kode
   ```

## Preview email tanpa kirim asli

Karena `.env` pakai SMTP asli (bukan MailHog/Mailpit), preview tampilan email tanpa benar-benar
mengirim bisa lewat (local environment saja):

- `/dev/mail-preview/approved`
- `/dev/mail-preview/rejected`

(ambil submission pertama yang ada di database — pastikan sudah ada data, mis. lewat `DemoSeeder`.)

## Test

```bash
php artisan test
```

Test pakai koneksi SQLite in-memory + `MAIL_MAILER=array` (lihat `phpunit.xml`), jadi tidak
pernah menyentuh database MySQL development atau mengirim email asli.

## Struktur penting

- `docs/spec.md` — spec domain (jangan diubah tanpa konfirmasi, terutama bagian 4 & 10).
- `CLAUDE.md` — aturan kerja & checklist fase untuk pengembangan lanjutan via Claude Code.
- `app/Services/EligibilityService.php` — mesin eligibility (inti sistem).
- `app/Services/ThresholdService.php` — hitung `course_thresholds` (percentile).
- `database/seeders/Data/ModuleCourseMap.php` — sumber kebenaran pemetaan modul ↔ matkul.
