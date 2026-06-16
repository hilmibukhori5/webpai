# Deploy ke Plesk — subfolder `math.ub.ac.id/pendaftaran-pai`

Panduan ini buat deploy project ini sebagai **Laravel kedua**, hidup berdampingan
sama Laravel #1 yang sudah live di docroot `httpdocs/public` (yang itu **tidak
disentuh sama sekali**). Konteks lengkap & alasan tiap keputusan ada di
percakapan deploy — ringkasannya di bawah.

## Layout akhir di server

```
httpdocs/
├── public/                          ← Laravel #1, JANGAN disentuh
│   └── pendaftaran-pai/              ← stub publik Laravel #2 (folder baru)
│       ├── index.php                 ← dari deploy/plesk-subfolder/index.php
│       ├── .htaccess                 ← dari deploy/plesk-subfolder/.htaccess
│       ├── favicon.ico, robots.txt   ← copy dari public/ project ini (opsional)
│       └── build/                    ← hasil `npm run build`, upload manual
└── pendaftaran-pai-app/              ← seluruh repo ini, di-deploy via Git
    ├── app/ bootstrap/ vendor/ storage/ routes/ ...
    └── .env                          ← dibuat manual, TIDAK ikut git
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
> kayak database terpisah (DB user yang sama otomatis bisa baca/tulis ke
> tabel Laravel #1 juga kalau mau). Tetap aman secara teknis selama tidak
> ada migration project ini yang sengaja menyentuh tabel tanpa prefix.

## 2. Setup repo di Plesk Git extension

Buka **Git** di Plesk, klik **Create repository**, isi:

| Field | Isi |
|---|---|
| Code location | **Remote repository** |
| Repository URL | `https://github.com/hilmibukhori5/webpai.git` |
| Repository name | `pendaftaran-pai` (bebas, asal beda dari repo Laravel #1) |
| Deployment mode | **Manual** dulu (lebih aman buat tahap awal — bisa ganti ke Automatic nanti kalau sudah yakin semua jalan) |
| Server path | **`/httpdocs/pendaftaran-pai-app`** ⚠️ |

> ⚠️ **WAJIB diganti** — defaultnya Plesk kadang nyaranin `/httpdocs/public`
> (itu folder Laravel #1 yang sudah live!). Kalau dipakai apa adanya, Plesk
> akan nge-pull kode Laravel #2 ke folder yang sama dengan Laravel #1 dan
> bisa merusak web yang sudah jalan. Pastikan jadi `/httpdocs/pendaftaran-pai-app`.

Centang **"Enable additional deployment actions"**, isi kotaknya dengan:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
ln -sfn "$(pwd)/storage/app/public" ../public/pendaftaran-pai/storage
```

Catatan tiap baris (cek pas testing, sesuaikan kalau error):
- Asumsinya command ini dieksekusi dengan **current directory = folder yang
  baru di-deploy** (`httpdocs/pendaftaran-pai-app`) — itu default Plesk Git
  deployment actions.
- `composer`/`php` harus yang ada di PATH shell Plesk. Kalau error
  "command not found" atau versi PHP-nya salah, ganti ke path eksplisit
  (cek di Plesk → PHP Settings buat tahu path binary PHP CLI-nya, biasanya
  mirip `/opt/plesk/php/8.4/bin/php`).
- Baris `ln -sfn` itu **pengganti** `php artisan storage:link` — sengaja
  bukan pakai command artisan itu karena folder public-nya dipindah,
  `storage:link` bawaan bakal salah lokasi. Symlink manual ini langsung
  nunjuk ke lokasi fisik yang benar.

Repo ini **public di GitHub** — kalau ternyata di-private-kan nanti, field
Repository URL butuh token/SSH key, kasih tau saya biar disesuaikan caranya.

Klik **Create**, lalu klik **Deploy** (karena mode Manual).

## 3. Buat `.env` di server

Lewat File Manager Plesk, buat file `httpdocs/pendaftaran-pai-app/.env`.
Isinya contoh ada di [`deploy/plesk-subfolder/production.env.example`](plesk-subfolder/production.env.example)
— copy isinya, lalu **isi semua bagian PLACEHOLDER** (DB credentials, mail
credentials, APP_KEY).

Generate `APP_KEY` di komputer kamu sendiri (bukan di server, karena gak ada
shell):
```
php artisan key:generate --show
```
Paste hasilnya (`base64:....`) ke baris `APP_KEY=` di `.env` server.

## 4. Buat folder stub publik

Lewat File Manager, buat folder `httpdocs/public/pendaftaran-pai/`, upload:
- [`deploy/plesk-subfolder/index.php`](plesk-subfolder/index.php)
- [`deploy/plesk-subfolder/.htaccess`](plesk-subfolder/.htaccess)
- (opsional) `favicon.ico` & `robots.txt` dari `public/` project ini

## 5. Build & upload asset front-end

Di komputer kamu (server Plesk kemungkinan gak ada Node):
```
npm install
npm run build
```
Upload **isi folder** `public/build/` (hasil build, bukan source-nya) ke
`httpdocs/public/pendaftaran-pai/build/` lewat File Manager (zip lalu extract
biasanya paling gampang). **Ulangi langkah ini tiap ada perubahan CSS/JS.**

## 6. Test

Buka `https://math.ub.ac.id/pendaftaran-pai`:
- [ ] Halaman welcome muncul, CSS ke-load (cek di DevTools, harus minta file
  dari `/pendaftaran-pai/build/assets/...`, bukan 404)
- [ ] Register & login jalan
- [ ] Dashboard mahasiswa tampil normal
- [ ] Coba upload bukti bayar (fitur ini butuh symlink storage jalan benar —
  kalau gagal/404 pas lihat file yang diupload, cek lagi langkah `ln -sfn` di
  deployment actions)
- [ ] Login admin jalan, nav & halaman admin normal

## Troubleshooting

**Semua route selain `/` jadi 404** — buka
`httpdocs/public/pendaftaran-pai/.htaccess`, uncomment baris
`RewriteBase /pendaftaran-pai/`. Ini gotcha umum kalau app ada di subfolder
dalam docroot yang sudah punya `.htaccess` rewrite sendiri (punya Laravel #1).

**Asset (CSS/JS) 404 atau salah alamat** — pastikan `ASSET_URL` di `.env`
sudah `https://math.ub.ac.id/pendaftaran-pai` (bukan kosong), lalu
`php artisan config:clear` lagi (atau tunggu deploy berikutnya, ada di
deployment actions). Kalau masih salah, kemungkinan perlu tambah
`base: '/pendaftaran-pai/build/'` di `vite.config.js` lalu `npm run build`
ulang — kasih tau saya hasilnya kalau sampai ke titik ini.

**Email gak terkirim** — cek `MAIL_USERNAME`/`MAIL_PASSWORD` di `.env`
(Gmail App Password, bukan password akun biasa), dan `QUEUE_CONNECTION=sync`
sudah benar (lihat catatan di `production.env.example`).

**Composer/PHP command not found di deployment actions** — ganti ke path
absolut (cek Plesk → PHP Settings buat versi PHP yang benar, dan cek lewat
File Manager apakah ada `composer.phar` atau alias `composer` yang Plesk
sediakan).

## Update / redeploy berikutnya

1. Push perubahan ke `main` di GitHub seperti biasa.
2. Plesk → Git → klik **Deploy** (kalau mode Manual) — otomatis jalanin
   composer install + migrate + symlink lagi lewat deployment actions.
3. Kalau ada perubahan CSS/JS: ulangi langkah 5 (build & upload manual) —
   ini **tidak** otomatis lewat Git, karena `public/build` sengaja
   di-`.gitignore`.
