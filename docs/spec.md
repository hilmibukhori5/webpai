# Sistem Penyetaraan Modul PAI — Spec & Playbook Claude Code

> Taruh file ini di repo Laravel kamu sebagai `docs/spec.md`.
> Di tiap prompt ke Claude Code, rujuk dengan `@docs/spec.md` supaya konteks domain konsisten.

---

## 1. Tujuan

Mahasiswa S1 Ilmu Aktuaria / S1 Matematika (Departemen Matematika UB) yang lulus matkul tertentu bisa
menyetarakan matkul tsb ke **Modul PAI level ASAI (A10–A70)**. Sistem otomatis menentukan
apakah mahasiswa **eligible** per modul (berdasarkan nilai), mahasiswa mengajukan + setuju bayar,
lalu admin menyetujui/menolak per modul dengan notifikasi email.

Ada **2 dimensi terpisah** yang sering ketukar—pisahkan baik-baik:

- **Kurikulum (Lama vs Baru)** → menentukan *kode matkul mana* yang masuk ke tiap modul.
- **Skema PKS (Lama vs Baru)** → menentukan *aturan kelulusan penyetaraan*.

Adendum PKS Lama/Baru **tidak** identik dengan Kurikulum Lama/Baru. Mereka dievaluasi independen.

---

## 2. Pemetaan Modul ↔ Matkul (per kurikulum)

| Modul PAI | Kode resmi ASAI | Persentil | Kurikulum BARU (kode – sks) | Kurikulum LAMA (kode – sks) |
|---|---|---|---|---|
| **A10 — Matematika Keuangan** | CF1 | 80 | Mat. Finansial I (MAA62043, 3) · Mat. Finansial II (MAA61041, 3) | Mat. Finansial I (MAA62009, 3) · Mat. Finansial II (MAA61015, 3) |
| **A20 — Probabilita & Statistika** | CF2 | 90 | Stat. Matematika I (MAA62003, 3) · Stat. Matematika II (MAA61007, 3) | Stat. Matematika I (MAA62003, 3) · Stat. Matematika II (MAA61007, 3) |
| **A30 — Ekonomi** | CF3 | 80 | Peng. Ek. Mikro (MAA62004, 3) · Peng. Ek. Makro (MAA61052, 3) | Peng. Ek. Mikro (MAA62004, 3) · Peng. Ek. Makro (MAA61009, 3) |
| **A40 — Akuntansi** | CF4 | 80 | Akuntansi Aktuaria I (MAA62042, 3) · Akuntansi Aktuaria II (MAA61044, 3) | Akuntansi Aktuaria I (MAA62007, 2) · Akuntansi Aktuaria II (MAA61022, 2) |
| **A50 — Metoda Statistika** | TA1 | 80 | Peng. Runtun Waktu (MAA62045, 3) · Analisis Data Survival (MAA61016, 3) · Model Linear (MAA62047, 3) | Peng. Runtun Waktu (MAA62011, 3) · Analisis Data Survival (MAA61016, 3) · Ekonometrika (MAA62023, 2) · Model Linear (MAA62013, 3) |
| **A60 — Matematika Aktuaria** | TA3 | 80 | Mat. Aktuaria I (MAA62048, 3) · Mat. Aktuaria II (MAA61033, 3) | Mat. Aktuaria I (MAA62028, 3) · Mat. Aktuaria II (MAA61033, 3) |
| **A70 — Pemodelan & Teori Risiko** | TA2 | 90 | Pemodelan Risiko Aktuaria (MAA62044, 3) · Teori Risiko & Kredibilitas Aktuaria (MAA61051, 3) | Pemodelan Aktuaria (MAA62008, 4) · Teori Risiko Aktuaria (MAA61035, 2) |

**Catatan kode bersama:** beberapa kode muncul di dua kurikulum (mis. MAA62003, MAA61007,
MAA62004, MAA61016, MAA61033). Artinya satu `course` bisa terhubung ke modul untuk
**dua** curriculum sekaligus (dua baris di tabel pivot).

**Catatan kode resmi & persentil (dikonfirmasi 2026-06-16):** `code` (A10–A70) tetap dipakai
sebagai identifier internal di database & UI (chip warna di bagian 10 tidak berubah).
`official_code` (CF1–CF4, TA1–TA3) cuma referensi tambahan ke penamaan resmi ASAI — disimpan
di kolom `pai_modules.official_code`. **Persentil beda per modul** (bukan satu nilai global),
disimpan di kolom `pai_modules.percentile`.

> ⚠️ **CEK ULANG** seluruh kode & sks di atas dengan sumber resmimu sebelum di-seed.

---

## 3. Skala Nilai (PERLU KONFIRMASI)

Asumsi skala UB (NH → bobot):

| NH | Bobot |
|----|------|
| A | 4.0 |
| B+ | 3.5 |
| B | 3.0 |
| C+ | 2.5 |
| C | 2.0 |
| D+ | 1.5 |
| D | 1.0 |
| E | 0.0 |

- **NA** = nilai angka (mis. 0–100), dipakai untuk perhitungan **percentile** (PKS Baru).
- **NH** = nilai huruf → dikonversi ke **bobot**, dipakai untuk rata-rata **> 3.5** (Adendum PKS Lama).

---

## 4. Aturan Eligibility

Sebuah modul **M** punya himpunan matkul komponen per kurikulum:
`courses_baru(M)` dan `courses_lama(M)`. Mahasiswa harus **lulus SEMUA** matkul komponen
modul (tidak boleh ada yang belum diambil / nilai E) agar bisa dievaluasi — lengkap lewat
`courses_baru(M)` **atau** lewat `courses_lama(M)` (tidak perlu keduanya; matkul berkode sama
yang dipakai di 2 kurikulum otomatis memenuhi keduanya sekaligus).

**Retake/duplikat nilai (dikonfirmasi 2026-06-16):** kalau satu mahasiswa (no_induk) punya
>1 baris `course_grades` untuk course yang sama, ambil baris dengan **NA tertinggi** (percobaan
terbaik) sebagai nilai yang dipakai untuk evaluasi.

### 4a. PKS Baru (percentile)
- Untuk tiap matkul: `batas_bawah(course)` = `PERCENTILE.INC(semua NA matkul itu, P)`
  di mana NA di-**pool dari semua semester/kelas**.
- `P` **berbeda per modul** (bukan satu nilai global), disimpan di `pai_modules.percentile`.
  Nilai yang dikonfirmasi (lihat bagian 2): CF1/CF3/CF4/TA1/TA3 = **80**, CF2/TA2 = **90**.
  Semua matkul komponen modul M memakai `P` milik modul M itu sendiri.
- Mahasiswa **eligible PKS Baru untuk M** jika `NA mahasiswa ≥ batas_bawah` di **semua** matkul komponen M.

### 4b. Adendum PKS Lama (> 3.5)
- Hitung **rata-rata bobot tertimbang SKS** atas semua matkul komponen M:
  `Σ(bobot_i × sks_i) / Σ(sks_i)`.
- **Eligible Adendum PKS Lama untuk M** jika hasil **> 3.5** (strictly greater).
- Contoh: A(4.0, 3sks) + B+(3.5, 3sks) = (12+10.5)/6 = 3.75 > 3.5 → eligible.
  B+ + B+ = 3.5 → **tidak** eligible.

### 4c. Decision tree (prioritas) — DIPERBARUI 2026-06-21

**Step 0 (pre-check tahun) — DIKONFIRMASI 2026-06-21:**
Sebelum evaluasi PKS, cek tahun akademik dari setiap `bestGrade` yang dipakai evaluasi
(field `course_grades.semester`, format "Genap 2324" / "Ganjil 2223", dst.). Ekstrak
4-digit kode tahun di akhir. Jika **ADA SATU PUN** kode tahun ≤ 2324 (artinya TA 23/24
atau lebih lama) → set `forceOldScheme = true`.

Efek `forceOldScheme`:
- PKS Baru (4a / percentile) **tidak dievaluasi** — dilewati.
- Adendum PKS Lama (4b / weighted average) tetap dievaluasi.
- Cek kode kurikulum di step `lama`-branch **diabaikan** (kode baru pun dapat `decision=lama`).

Rasional: mahasiswa yang mengambil matkul di TA 23/24 atau lebih lama diperlakukan
sepenuhnya sebagai era kurikulum lama — skema mereka Adendum PKS Lama tanpa syarat kode.

```
// Step 0
forceOldScheme = any(yearCode(bestGrade.semester) <= 2324 for all matched courses)

evaluate(student, module):
  baru = forceOldScheme ? false : eligibleBaru(student, module)   // 4a dilewati jika old
  lama = eligibleLama(student, module)                             // 4b selalu dihitung

  // Adendum PKS Lama DIUTAMAKAN — lebih murah (Rp500.000 vs Rp550.000)
  if lama AND (forceOldScheme OR >=1 kode matkul LAMA):
                                         -> decision = "lama"   (Rp500.000)
  elif baru:                             -> decision = "baru"   (Rp550.000)
  elif lama (semua kode BARU, new year): -> decision = "none"   // wajib PKS Baru tapi gagal
  else:                                  -> decision = "none"
```

**Contoh:**
- Nilai TA 24/25, kode lama, lolos 4b DAN lolos percentile → **decision=lama** (lama
  diutamakan karena lebih murah, meski PKS Baru juga lolos).
- Matkul 1 nilai TA 23/24, Matkul 2 nilai TA 24/25 → `forceOldScheme=true` → PKS Baru
  diblokir walau NA lolos percentile → decision=lama kalau 4b lolos.
- Semua nilai TA 24/25+, kode baru → `forceOldScheme=false` → evaluasi Adendum PKS Lama dulu
  (tapi butuh kode lama — tidak ada → skip), lalu PKS Baru.

**Catatan kode kurikulum (berlaku HANYA saat forceOldScheme=false):** Adendum PKS Lama cuma valid
bagi mahasiswa yang matkul-nya berkode kurikulum lama. Kalau mahasiswa punya matkul kode
baru + nilai baru (≥TA 24/25) + lolos 4b, tetap `decision=none` — bukan celah bagi
mahasiswa kurikulum baru yang gagal percentile PKS Baru.

Output yang dibutuhkan UI per modul: `eligible_baru` (bool), `eligible_lama` (bool),
`decision` (`baru|lama|none`), `price`, `component_grades` (buat ditampilkan ke admin).

> Tampilkan ke mahasiswa 3 state: **Eligible (PKS Baru)** / **Eligible (Adendum PKS Lama)** / **Belum Eligible**,
> bukan cuma enable/disable, biar dia paham alasannya.

---

## 5. Biaya
- Adendum PKS Lama: **Rp500.000 / modul**
- PKS Baru: **Rp550.000 / modul**

---

## 6. Flow

**Mahasiswa:** Register → Login → Dashboard (kartu per modul A10–A70 + status eligibility +
tombol *Ajukan Penyetaraan*) → klik → form persetujuan (bersedia diajukan + bersedia bayar,
tampilkan skema & harga) → submit (status `pending`).

**Admin:** Login → Dashboard list **per mahasiswa** (rekap: jumlah disetujui / pending / ditolak) →
*Detail* (daftar modul yang diajukan + rincian nilai + skema + tombol **Setujui**/**Tolak** per modul).
- Setujui → email "Modul … telah disetujui …".
- Tolak → ada field alasan; submit → email berisi alasan penolakan.

---

## 7. Data Model (acuan)

```
users(id, name, email, password, role[admin|student])
students(id, user_id, no_induk UNIQUE, nama, prodi)
pai_modules(id, code UNIQUE, name, official_code, percentile)  // A10..A70; official_code = CF1-CF4/TA1-TA3; percentile = P per modul (bagian 2)
courses(id, code UNIQUE, name, sks)
module_course(id, pai_module_id, course_id, curriculum[lama|baru])  // pivot
course_grades(id, course_id, semester, no_induk, nama, na, nh, grade_point)
course_thresholds(id, course_id UNIQUE, percentile, threshold_na, computed_at)
submissions(id, student_id, pai_module_id, scheme[lama|baru], price,
            status[pending|approved|rejected], rejection_reason NULLABLE,
            reviewed_by NULLABLE, reviewed_at NULLABLE, timestamps)
submission_courses(id, submission_id, course_id, na, nh, grade_point)  // snapshot audit
```
- Mahasiswa dicocokkan ke nilai lewat **`no_induk`** (mahasiswa input NIM saat register / profil).
- 1 `submission` = 1 mahasiswa × 1 modul (status per modul, sesuai requirement admin).

---

## 8. Playbook Prompt untuk Claude Code

Kerjakan **bertahap**. Di tiap fase: suruh dia *rencana dulu sebelum nulis kode*,
review, lalu **commit** sebelum lanjut fase berikutnya. Selalu sertakan `@docs/spec.md`.

### Fase 0 — Setup & konvensi
```
Baca @docs/spec.md. Ini project Laravel buat sistem penyetaraan modul PAI.
Jangan tulis kode dulu. Buatkan:
1) Ringkasan arsitektur (folder, layer: Models, Services, Form Requests, Policies, Mailables).
2) Rencana auth: 2 role (admin, student) pakai [Breeze/Jetstream — pilih & jelaskan], + middleware role.
3) Daftar package yang perlu (mis. maatwebsite/excel untuk import).
Tunggu konfirmasiku sebelum mulai.
```

### Fase 1 — Schema, model, seeder master
```
Implement skema dari bagian 7 @docs/spec.md: migration + model + relationship Eloquent.
Lalu buat seeder untuk pai_modules (A10–A70), courses, dan pivot module_course
(curriculum lama/baru) PERSIS sesuai tabel di bagian 2 @docs/spec.md.
Buat juga config skala nilai (bagian 3) sebagai array config + helper konversi NH->bobot.
Tampilkan diff migration & seeder, jalankan migrate:fresh --seed, tunjukkan hasilnya.
```

### Fase 2 — Import nilai + hitung batas bawah (percentile)
```
Buat fitur import nilai (admin) dari Excel/CSV per matkul per semester.
Kolom: No Induk, Nama, NA, NH. Tiap import wajib pilih course + label semester (mis. "Genap 2223").
Simpan ke course_grades (isi grade_point dari NH via helper).
Setelah import, recompute course_thresholds untuk course terkait:
threshold_na = PERCENTILE.INC(semua NA course itu dari SEMUA semester, P),
di mana P diambil dari `pai_modules.percentile` milik modul tempat course itu jadi komponen
(lihat bagian 2 & 4a — P beda per modul, bukan satu nilai global; tiap course hanya jadi
komponen 1 modul, jadi P-nya tidak ambigu). Buat command artisan `php artisan thresholds:recompute`
juga. Sertakan validasi & contoh file.
```

### Fase 3 — Mesin eligibility (INTI, harus ada test)
```
Buat App\Services\EligibilityService dengan method evaluate(Student, PaiModule)
yang mengembalikan DTO: eligible_baru, eligible_lama, decision(baru|lama|none),
price, component_grades, reason. Ikuti PERSIS aturan di bagian 4 @docs/spec.md
(PKS Baru = NA >= batas_bawah di semua matkul; Adendum PKS Lama = rata2 bobot tertimbang SKS > 3.5;
decision tree 4c). Cocokkan nilai mahasiswa via no_induk.
WAJIB tulis unit test untuk kasus: eligible baru, eligible lama (ada kode kurikulum lama),
eligible lama tapi semua kode baru (-> none), tidak lengkap matkulnya, batas tepat 3.5 (-> gagal).
```
> Aku review output fase ini dulu sebelum lanjut ke UI — ini bagian paling rawan.

### Fase 4 — Sisi mahasiswa
```
Bangun area mahasiswa: register (input no_induk, prodi) + login + dashboard.
Dashboard: kartu per modul A10–A70 pakai EligibilityService. Tiap kartu tampilkan
3 state (Eligible PKS Baru / Eligible Adendum PKS Lama / Belum Eligible) + alasan singkat.
Tombol "Ajukan Penyetaraan" hanya aktif kalau decision != none -> buka form persetujuan
(checkbox bersedia diajukan + bersedia bayar, tampilkan skema & harga dari bagian 5).
Submit -> buat submissions (status pending) + snapshot submission_courses.
Cegah pengajuan ganda untuk modul yang sudah pending/approved. UI Blade + Tailwind, simple & rapi.
```

### Fase 5 — Sisi admin
```
Bangun area admin: dashboard list PER MAHASISWA (bukan per modul) dengan rekap
jumlah approved/pending/rejected. Tombol Detail -> tampilkan tiap modul yang diajukan +
rincian nilai komponen (dari submission_courses) + skema + harga, dengan tombol
Setujui & Tolak PER MODUL. Tolak menampilkan field alasan (wajib).
Update status submission per modul. Pakai Policy biar cuma admin.
```

### Fase 6 — Email
```
Buat 2 Mailable: ApprovedModule (kirim saat Setujui: "Modul {kode-nama} telah disetujui")
dan RejectedModule (kirim saat Tolak: sertakan alasan dari field admin).
Trigger saat aksi admin di fase 5. Pakai queue (database driver). Sediakan preview di MailHog/Mailpit.
```

### Fase 7 — Polish & demo
```
Tambahkan: seeder demo (1 admin, beberapa student + nilai contoh yang mengaktifkan
tiap cabang decision tree), validasi & error handling, dan README cara setup + cara import.
Jalankan semua test, pastikan hijau.
```

---

## 9. Tips ngeprompt Claude Code
- **Satu fase satu sesi**, commit di antaranya. Konteks tetap bersih, gampang di-revert.
- Selalu `@docs/spec.md` + suruh **rencana dulu** ("jangan tulis kode sampai aku setuju").
- Untuk fase 3, **paksa dia bikin test** dulu—itu jaring pengaman aturan domainnya.
- Kalau dia mulai nebak aturan bisnis, hentikan & arahkan balik ke bagian 4 spec.
- Minta dia **tunjukkan diff** & jalankan migrasi/test, jangan cuma klaim "sudah jadi".

---

## 10. Design System (untuk app Laravel + Tailwind)

> Filosofi: **clean, flat-ish, modern, tapi colorful lewat identitas warna per modul.**
> Netral di mana-mana, warna dipakai untuk *makna* (identitas modul + status), bukan dekorasi acak.

### Font
- Heading/UI: **Plus Jakarta Sans** (atau Inter). Body: Inter.
- Load via Google Fonts / bunny.net. Hanya 2 berat utama: 400 & 500/600.

### Palet
- **Base / page bg**: `slate-50` (#F8FAFC). Surface/kartu: `white`.
- **Border**: `slate-200`. Text: `slate-900` (utama), `slate-500` (muted).
- **Primary (aksi utama / brand)**: indigo→violet. `indigo-600 #4F46E5` → `violet-600 #7C3AED` (boleh gradient halus di tombol primary).
- **Warna identitas modul** (chip kode A10–A70):
  | Modul | Tailwind | Hex |
  |---|---|---|
  | A10 | indigo-500 | #6366F1 |
  | A20 | sky-500 | #0EA5E9 |
  | A30 | emerald-500 | #10B981 |
  | A40 | amber-500 | #F59E0B |
  | A50 | rose-500 | #F43F5E |
  | A60 | orange-500 | #F97316 |
  | A70 | lime-600 | #65A30D |
- **Status (badge)** — semantik, konsisten:
  - Eligible PKS Baru → `emerald` (bg emerald-50, text emerald-700, ikon check)
  - Eligible Adendum PKS Lama → `blue` (bg blue-50, text blue-700, ikon check)
  - Belum eligible → `slate` (bg slate-100, text slate-500, ikon lock) + tombol disabled

### Token komponen
- **Kartu**: `bg-white rounded-2xl border border-slate-200 p-5`, hover `shadow-md` transisi halus. Shadow default sangat tipis (`shadow-sm`) atau none.
- **Radius**: kartu `rounded-2xl`, tombol/input `rounded-xl`, chip `rounded-lg`, badge `rounded-full`.
- **Tombol primary**: `bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2.5 font-medium` (boleh `bg-gradient-to-r from-indigo-600 to-violet-600`).
- **Spasi**: lega — `gap-4`/`gap-6`, `p-5`/`p-6`. Grid kartu modul: `grid sm:grid-cols-2 lg:grid-cols-3 gap-4`.
- **Metric card** (Eligible / Diajukan / Disetujui): `bg-white rounded-2xl border p-5`, label `text-sm text-slate-500`, angka `text-2xl font-semibold`.
- Ikon: Lucide (`lucide-laravel`/blade-ui) atau Tabler. Outline, ukuran 16–20px.
- Sentence case di semua label. Hindari ALL CAPS.

### Pola layout
- **Dashboard mahasiswa**: top bar (brand + profil) → baris 3 metric card → grid kartu modul (chip kode berwarna kiri-atas + badge status kanan-atas + nama modul + matkul komponen + footer: harga + tombol Ajukan/disabled).
- **Admin**: sidebar tipis + tabel list per-mahasiswa (rekap badge approved/pending/rejected), drawer/modal "Detail" berisi rincian per modul + tombol Setujui/Tolak.
- **Dark mode**: opsional; kalau dibuat, pakai `slate-900` surface + `slate-800` border.

### Prompt styling untuk Claude Code
```
Baca bagian 10 @docs/spec.md (Design System). Terapkan ke seluruh UI Blade + Tailwind.
Buat dulu lapisan dasar TANPA mengubah logika:
1) Setup font Plus Jakarta Sans + Inter, dan extend tailwind.config (warna primary indigo/violet,
   warna modul A10–A70, radius, fontFamily).
2) Buat komponen Blade reusable: <x-module-card>, <x-status-badge scheme="baru|lama|none">,
   <x-metric-card>, <x-btn variant="primary|ghost|disabled">. Patuhi token di bagian 10.
3) Rombak dashboard mahasiswa pakai komponen itu: chip kode berwarna per modul + badge status
   semantik (emerald=PKS Baru, blue=Adendum PKS Lama, slate+lock=belum eligible), tombol Ajukan auto-disable
   saat decision=none. Grid responsif sm:2 / lg:3.
Tunjukkan screenshot/markup hasilnya. Jangan sentuh EligibilityService atau migration.
```
