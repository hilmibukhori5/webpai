# Deploy ke Plesk — subfolder `math.ub.ac.id/pendaftaran-pai`

Laravel kedua, hidup berdampingan sama Laravel #1 yang sudah live di
`httpdocs/public` (yang itu **tidak disentuh sama sekali**). Ikuti urutan
di bawah dari atas ke bawah, gak ada cabang/pilihan — semua keputusan
sudah ditentukan.

## File yang sudah disiapkan (ada di Desktop kamu)

| File | Isinya |
|---|---|
| `pendaftaran-pai-database.sql` | Struktur tabel + data dasar (modul A10–A70, daftar matkul) — tabel sudah diberi prefix `pai2_` biar gak bentrok sama tabel Laravel #1 di database yang sama |
| `pendaftaran-pai-app-code.zip` | Kode aplikasi Laravel (TANPA folder `vendor/`, dibuat di langkah 3) |
| `pendaftaran-pai-public-stub.zip` | `index.php` + `.htaccess` + asset CSS/JS — ini yang nanti diakses browser |

## Langkah 1 — Import database

1. Plesk → domain `math.ub.ac.id` → **Databases** → buka **phpMyAdmin**
   buat database yang sudah ada (yang dipakai Laravel #1)
2. Tab **Import** → pilih file `pendaftaran-pai-database.sql` dari Desktop → **Go**
3. Selesai, harusnya muncul tabel-tabel baru berawalan `pai2_` (mis.
   `pai2_users`, `pai2_pai_modules`, dst) berdampingan sama tabel Laravel
   #1 yang sudah ada — **tidak ada tabel Laravel #1 yang berubah**

## Langkah 2 — Upload kode aplikasi

1. Plesk → **File Manager** → masuk ke `httpdocs/`
2. Bikin folder baru, namai **`pendaftaran-pai-app`**
3. Masuk ke folder itu → **Upload** → pilih `pendaftaran-pai-app-code.zip`
4. Klik kanan file zip itu → **Extract**
5. Hapus file zip-nya (yang ke-extract sudah cukup)

## Langkah 3 — Install dependency (bikin folder `vendor/`)

`vendor/` itu folder isi library Laravel & paket-paketnya, ukurannya besar
(~90MB) makanya gak diupload manual — di sini Plesk yang download/bikin
otomatis:

1. Plesk → cari menu **Composer** (beda dari File Manager/Git, biasanya
   ada di sidebar domain)
2. Arahkan ke file `httpdocs/pendaftaran-pai-app/composer.json`
3. Klik **Install** (pilih mode production / tanpa "--dev" kalau ada
   opsinya)
4. Tunggu sampai selesai — kalau sukses, folder `vendor/` muncul di
   `httpdocs/pendaftaran-pai-app/`

## Langkah 4 — Buat file `.env`

1. File Manager → masuk `httpdocs/pendaftaran-pai-app/`
2. Bikin file baru namanya **`.env`** (titik di depan, tanpa nama lain)
3. Isi dengan teks ini, **ganti semua yang ada tulisan `GANTI_INI`**:

```env
APP_NAME="Pendaftaran PAI"
APP_ENV=production
APP_KEY=GANTI_INI
APP_DEBUG=false
APP_URL=https://math.ub.ac.id/pendaftaran-pai
ASSET_URL=https://math.ub.ac.id/pendaftaran-pai

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=GANTI_INI
DB_PORT=3306
DB_DATABASE=GANTI_INI
DB_USERNAME=GANTI_INI
DB_PASSWORD=GANTI_INI
DB_PREFIX=pai2_

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/pendaftaran-pai
SESSION_DOMAIN=null
SESSION_COOKIE=pendaftaran_pai_session

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=GANTI_INI
MAIL_PASSWORD=GANTI_INI
MAIL_FROM_ADDRESS=GANTI_INI
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

Yang harus diisi:
- **`APP_KEY`** — minta saya generate, atau jalankan sendiri di komputer:
  `php artisan key:generate --show`, paste hasilnya (`base64:....`)
- **`DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD`** — lihat di
  Plesk → Databases, klik database yang dipakai langkah 1, info
  koneksinya ada di situ
- **`MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_FROM_ADDRESS`** — email
  Gmail yang dipakai buat kirim notifikasi (`MAIL_PASSWORD` pakai
  [App Password Gmail](https://myaccount.google.com/apppasswords), bukan
  password akun biasa)

## Langkah 5 — Upload bagian publik (yang diakses browser)

1. File Manager → masuk `httpdocs/public/`
2. Bikin folder baru, namai **`pendaftaran-pai`**
3. Masuk ke folder itu → **Upload** → `pendaftaran-pai-public-stub.zip`
4. Klik kanan → **Extract** → hapus file zip-nya

## Langkah 6 — Bikin symlink storage (sekali jalan)

1. Upload file `deploy/plesk-subfolder/setup-once.php` (dari repo ini) ke
   `httpdocs/public/pendaftaran-pai/` (sebelah `index.php`)
2. **Edit file itu di server** (klik kanan → Edit/Code Editor di File
   Manager), cari baris `const TOKEN = 'GANTI_DENGAN_STRING_ACAK_KAMU_SENDIRI';`,
   ganti isinya dengan string acak sendiri (boleh asal-asalan, contoh:
   `'xyz789rahasia123'`) — **jangan** pakai contoh ini, bikin sendiri.
   Save.
3. Buka di browser, ganti `TOKEN_KAMU` sesuai yang baru kamu isi:
   ```
   https://math.ub.ac.id/pendaftaran-pai/setup-once.php?token=TOKEN_KAMU
   ```
4. Kalau tulisannya `SUKSES` + "File ini sudah otomatis terhapus" → beres.
   Kalau ada `[GAGAL]`, baca pesannya (biasanya berarti langkah 2/3 belum
   selesai), perbaiki, refresh halaman yang sama buat coba lagi.
4. **Cek lagi lewat File Manager** — pastikan file `setup-once.php` itu
   sudah benar-benar hilang. Kalau masih ada, hapus manual.

## Langkah 7 — Buat akun admin

Database hasil import (Langkah 1) **belum ada akun apapun** (sengaja,
biar gak ada akun demo/test yang ikut ke production). Caranya:

1. Buka `https://math.ub.ac.id/pendaftaran-pai/register`, daftar pakai
   email & data kamu sendiri (jadinya akun "mahasiswa" dulu)
2. Buka phpMyAdmin lagi → tabel **`pai2_users`** → cari baris email kamu
   → Edit → ubah kolom `role` dari `student` jadi `admin` → Save

## Langkah 8 — Test

Buka `https://math.ub.ac.id/pendaftaran-pai`:
- [ ] Halaman welcome muncul, CSS ke-load (bukan tampilan polos tanpa style)
- [ ] Login pakai akun admin dari Langkah 7 berhasil, masuk ke dashboard admin
- [ ] Coba register akun mahasiswa baru, login, dashboard tampil normal
- [ ] Coba fitur upload bukti bayar di 1 modul (butuh symlink dari Langkah 6)

## Troubleshooting

**Semua halaman selain `/` jadi 404** — buka
`httpdocs/public/pendaftaran-pai/.htaccess`, uncomment baris
`RewriteBase /pendaftaran-pai/` (hapus tanda `#` di depannya).

**CSS/tampilan polos gak ada style** — cek `ASSET_URL` di `.env` sudah
`https://math.ub.ac.id/pendaftaran-pai`.

**Email gak terkirim** — cek `MAIL_USERNAME`/`MAIL_PASSWORD` (App
Password Gmail, bukan password akun biasa).

**Halaman error 500** — biasanya `.env` ada yang salah/`APP_KEY` kosong,
atau `vendor/` belum lengkap (ulangi Langkah 3).

**Upload bukti bayar gagal/file gak kebuka** — symlink dari Langkah 6
belum jalan, ulangi langkah itu.

Kalau macet di langkah manapun, kasih tau saya persis di langkah berapa
dan pesan error-nya apa.
