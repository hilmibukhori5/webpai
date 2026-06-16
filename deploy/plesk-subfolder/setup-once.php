<?php

/**
 * Script SEKALI PAKAI: jalankan migrate + bikin symlink storage, tanpa
 * butuh akses shell/SSH (cuma dibuka lewat browser). Taruh sementara di
 * httpdocs/public/pendaftaran-pai/setup-once.php (sebelah index.php),
 * buka https://math.ub.ac.id/pendaftaran-pai/setup-once.php?token=...
 *
 * ⚠️ WAJIB DIHAPUS setelah sukses dipakai -- script ini otomatis coba
 * hapus dirinya sendiri begitu semua langkah sukses, tapi CEK LAGI manual
 * lewat File Manager kalau-kalau auto-delete-nya gagal (mis. permission).
 *
 * Ganti TOKEN di bawah sebelum upload kalau mau, atau biarkan -- yang
 * penting JANGAN dibagikan/expose ke orang lain selain kamu sendiri.
 */
const TOKEN = 'f71257e208674002e82a456eb76a8916';

if (($_GET['token'] ?? '') !== TOKEN) {
    http_response_code(403);
    exit('Token salah/tidak ada. Buka dengan ?token=...');
}

header('Content-Type: text/plain; charset=utf-8');

$appBase = __DIR__.'/../../pendaftaran-pai-app';

echo "=== Setup pendaftaran-pai ===\n\n";

if (! is_dir($appBase)) {
    exit("GAGAL: folder app tidak ketemu di {$appBase}\n".
        "Pastikan kode Laravel sudah diupload+extract ke httpdocs/pendaftaran-pai-app/ dulu.\n");
}

if (! file_exists($appBase.'/vendor/autoload.php')) {
    exit("GAGAL: vendor/autoload.php tidak ada di {$appBase}.\n".
        "Jalankan Composer install dulu (Plesk -> Composer extension) sebelum buka script ini.\n");
}

$ok = true;

// ---------- 1. Bootstrap Laravel ----------
try {
    require $appBase.'/vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
    $app = require $appBase.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "[OK] Laravel berhasil di-bootstrap.\n\n";
} catch (\Throwable $e) {
    echo '[GAGAL] Bootstrap Laravel error: '.$e->getMessage()."\n";
    exit("\nCek lagi .env (terutama APP_KEY, DB_*) lalu coba refresh halaman ini.\n");
}

// ---------- 2. Migrate ----------
echo "=== Menjalankan migrate --force ===\n";
try {
    $exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();
    if ($exitCode === 0) {
        echo "[OK] Migrate sukses.\n\n";
    } else {
        echo "[GAGAL] Migrate exit code {$exitCode}.\n\n";
        $ok = false;
    }
} catch (\Throwable $e) {
    echo '[GAGAL] Migrate error: '.$e->getMessage()."\n\n";
    $ok = false;
}

// ---------- 3. Symlink storage ----------
echo "=== Membuat symlink storage ===\n";
$target = $appBase.'/storage/app/public';
$link = __DIR__.'/storage';

if (file_exists($link) || is_link($link)) {
    echo "[OK] Symlink/folder 'storage' sudah ada di {$link}, dilewati.\n\n";
} elseif (! function_exists('symlink')) {
    echo "[GAGAL] Fungsi symlink() di-disable di server ini. Minta admin\n".
        "aktifkan, atau hubungi saya buat cari cara lain.\n\n";
    $ok = false;
} else {
    $success = @symlink($target, $link);
    if ($success) {
        echo "[OK] Symlink dibuat: {$link} -> {$target}\n\n";
    } else {
        echo "[GAGAL] symlink() gagal (cek permission folder).\n\n";
        $ok = false;
    }
}

// ---------- 4. Self-delete kalau semua sukses ----------
echo "=== Hasil akhir ===\n";
if ($ok) {
    echo "Semua langkah SUKSES.\n";
    if (@unlink(__FILE__)) {
        echo "File ini sudah otomatis terhapus. Selesai!\n";
    } else {
        echo "PENTING: hapus file ini MANUAL lewat File Manager sekarang juga\n".
            "(httpdocs/public/pendaftaran-pai/setup-once.php) -- auto-delete gagal\n".
            "(kemungkinan permission).\n";
    }
} else {
    echo "Ada langkah yang GAGAL (lihat di atas). File ini BELUM dihapus,\n".
        "perbaiki dulu masalahnya lalu refresh halaman ini buat coba lagi.\n";
}
