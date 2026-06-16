# Deploy ke Plesk — subfolder `math.ub.ac.id/pendaftaran-pai`

Panduan ini buat deploy project ini sebagai **Laravel kedua**, hidup berdampingan
sama Laravel #1 yang sudah live di docroot `httpdocs/public` (yang itu **tidak
disentuh sama sekali**).

**Jalur yang dipakai: File Manager + Composer extension** (bukan Git
extension) — lebih sedikit hal yang harus dipahami, kamu kontrol penuh tiap
langkahnya. Konsekuensinya: update kode berikutnya harus upload ulang manual
(gak otomatis kayak `git pull`), tapi untuk deploy ini itu trade-off yang
wajar. (Kalau nanti berubah pikiran mau pakai Git extension, ada catatannya
di paling bawah.)

## Layout akhir di server

```
httpdocs/
├── public/                          ← Laravel #1, JANGAN disentuh
│   └── pendaftaran-pai/              ← stub publik Laravel #2 (folder baru)
│       ├── index.php                 ← dari pendaftaran-pai-app-code.zip
│       ├── .htaccess
│       ├── favicon.ico, robots.txt
│       ├── build/                    ← hasil `npm run build`
│       └── setup-once.php            ← SEMENTARA, dihapus setelah dipakai
└── pendaftaran-pai-app/              ← kode Laravel #2 (upload manual)
    ├── app/ bootstrap/ config/ database/ routes/ storage/ ...
    ├── vendor/                       ← dibuat Plesk Composer extension
    └── .env                          ← dibuat manual
```

Kenapa dipisah begini: domain `math.ub.ac.id` docroot-nya sudah terkunci ke
`httpdocs/public` (punya Laravel #1), dan akun Plesk-nya tidak bisa bikin
subdomain. Jadi kode Laravel #2 ditaruh di luar `public/` (aman, gak ke-serve
langsung), dan cuma file depan (`index.php` + `.htaccess` + asset build) yang
ditaruh di `public/pendaftaran-pai/` sebagai "pintu masuk" fisik.

## 1. Database

**Kalau plan hosting izinkan bikin database baru**: di Plesk → Databases,
buat database MySQL baru buat project ini. Catat host/nama database/
username/password.

**Kalau cuma boleh 1 database per domain** (sudah dipakai Laravel #1):
pakai database yang sama, tapi **WAJIB set `DB_PREFIX`** di `.env` (lihat
[`production.env.example`](plesk-subfolder/production.env.example)) supaya
semua tabel project ini (termasuk tabel `migrations` bawaan Laravel) dapat
prefix unik dan tidak collision sama tabel Laravel #1 yang sudah ada di
database itu — sudah didukung di `config/database.php` (`'prefix' =>
env('DB_PREFIX', '')`). Ambil host/nama database/username/password dari
entri database yang sudah ada di Plesk → Databases (bukan dari file Laravel
#1 — gak perlu sentuh kode/file punya Laravel #1 sama sekali).

> Catatan: prefix cuma misahin tabel, bukan benar-benar mengisolasi data
> kayak database terpisah. Tetap aman secara teknis selama tidak ada
> migration project ini yang sengaja menyentuh tabel tanpa prefix.

## 2. Upload kode Laravel ke `httpdocs/pendaftaran-pai-app/`

Sudah disiapkan zip-nya (`pendaftaran-pai-app-code.zip` di Desktop kamu) --
isinya kode app TANPA `vendor/` dan TANPA `node_modules/` (biar kecil &
upload cepat, ~230KB), juga tanpa `.env` (dibuat manual di langkah 4).

1. File Manager → masuk ke `httpdocs/`
2. Bikin folder baru **`pendaftaran-pai-app`**
3. Masuk ke folder itu, **Upload** `pendaftaran-pai-app-code.zip`
4. Klik kanan zip-nya → **Extract** (isinya didesain supaya extract
   langsung jadi `app/`, `bootstrap/`, `routes/`, dst — bukan ketumpuk
   folder tambahan)
5. Hapus file zip-nya setelah ke-extract

## 3. Jalankan Composer install

Di Plesk, cari menu **Composer** (terpisah dari Git extension), arahkan ke
`httpdocs/pendaftaran-pai-app/composer.json`, jalankan **Install**
(mode production / tanpa `--dev` kalau ada opsinya). Ini yang bikin folder
`vendor/` muncul -- tanpa ini Laravel-nya belum bisa jalan.

## 4. Buat `.env`

Lewat File Manager, buat file baru `httpdocs/pendaftaran-pai-app/.env`.
Isinya contoh ada di [`production.env.example`](plesk-subfolder/production.env.example)
— copy isinya, lalu **isi semua bagian PLACEHOLDER** (DB credentials, mail
credentials, APP_KEY).

Generate `APP_KEY` (jalankan di komputer kamu, bukan di server):
```
php artisan key:generate --show
```
Paste hasilnya (`base64:....`) ke baris `APP_KEY=`.

## 5. Buat folder stub publik

Folder `httpdocs/public/pendaftaran-pai/` isinya sudah disiapkan juga di
`pendaftaran-pai-public-stub.zip` (Desktop kamu) — `index.php`, `.htaccess`,
`favicon.ico`, `robots.txt`, dan `build/` (hasil `npm run build`).

1. File Manager → masuk `httpdocs/public/`
2. Bikin folder baru **`pendaftaran-pai`**
3. Upload `pendaftaran-pai-public-stub.zip` ke situ, **Extract**, hapus
   zip-nya

## 6. Jalankan setup sekali (migrate + symlink storage)

Upload [`deploy/plesk-subfolder/setup-once.php`](plesk-subfolder/setup-once.php)
ke `httpdocs/public/pendaftaran-pai/setup-once.php` (sebelah `index.php`).

Buka di browser:
```
https://math.ub.ac.id/pendaftaran-pai/setup-once.php?token=f71257e208674002e82a456eb76a8916
```
Baca outputnya — kalau semua `[OK]`, file itu otomatis menghapus dirinya
sendiri. **Cek lagi manual lewat File Manager** kalau-kalau auto-delete-nya
gagal (hapus manual kalau masih ada).

> Kalau ada `[GAGAL]`, baca pesan errornya (biasanya soal `.env` yang belum
> lengkap, atau `vendor/` belum ada karena langkah 3 belum jalan) -- benerin
> dulu, refresh halaman yang sama buat coba lagi (token-nya boleh dipakai
> berkali-kali sampai sukses, baru habis itu hapus file-nya).

## 7. Test

Buka `https://math.ub.ac.id/pendaftaran-pai`:
- [ ] Halaman welcome muncul, CSS ke-load (DevTools: file dari
  `/pendaftaran-pai/build/assets/...`, bukan 404)
- [ ] Register & login jalan
- [ ] Dashboard mahasiswa tampil normal
- [ ] Coba upload bukti bayar (butuh symlink storage dari langkah 6)
- [ ] Login admin jalan, nav & halaman admin normal

## Troubleshooting

**Semua route selain `/` jadi 404** — buka
`httpdocs/public/pendaftaran-pai/.htaccess`, uncomment baris
`RewriteBase /pendaftaran-pai/`. Gotcha umum kalau app ada di subfolder
dalam docroot yang sudah punya `.htaccess` rewrite sendiri (punya Laravel #1).

**Asset (CSS/JS) 404 atau salah alamat** — pastikan `ASSET_URL` di `.env`
sudah `https://math.ub.ac.id/pendaftaran-pai`. Kalau masih salah,
kemungkinan perlu tambah `base: '/pendaftaran-pai/build/'` di
`vite.config.js` lalu `npm run build` ulang — kasih tau saya hasilnya kalau
sampai ke titik ini.

**Email gak terkirim** — cek `MAIL_USERNAME`/`MAIL_PASSWORD` (Gmail App
Password, bukan password akun biasa), dan `QUEUE_CONNECTION=sync` di `.env`.

**`setup-once.php` bilang vendor/autoload.php tidak ada** — langkah 3
(Composer install) belum jalan/gagal, cek lagi di Plesk → Composer.

**`symlink()` di-disable** — beberapa hosting matikan fungsi ini buat
keamanan. Kalau kejadian, kasih tau saya, kita cari cara lain (mis. ubah
config disk `public` supaya simpan file langsung di folder yang ke-serve).

## Update kode berikutnya

Karena bukan lewat Git, update = ulangi langkah 2 (upload+extract zip kode
terbaru) dan/atau langkah 5 (asset terbaru). `.env` dan `storage/` yang
sudah ada di server **jangan ketimpa** — kalau extract zip menimpa folder
yang sama, pastikan File Manager-nya merge bukan replace total, atau upload
manual file yang berubah saja.

## Alternatif: pakai Plesk Git extension

Kalau nanti mau coba jalur otomatis (auto `composer install` + `migrate`
tiap push ke GitHub), Plesk Git extension punya field **"Enable additional
deployment actions"** buat jalanin shell command tiap deploy. Server path
**wajib** `/httpdocs/pendaftaran-pai-app` (⚠️ **bukan** `/httpdocs/public`,
itu folder Laravel #1 yang live). Command yang dipakai sama seperti
`setup-once.php` tapi versi shell:
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
ln -sfn "$(pwd)/storage/app/public" ../public/pendaftaran-pai/storage
```
Tanya saya lagi kalau mau pindah ke jalur ini.
