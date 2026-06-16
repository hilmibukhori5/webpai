<?php

/**
 * Script SEKALI PAKAI: bikin symlink storage, tanpa butuh akses shell/SSH
 * (cuma dibuka lewat browser). Taruh sementara di
 * httpdocs/public/pendaftaran-pai/setup-once.php (sebelah index.php),
 * buka https://math.ub.ac.id/pendaftaran-pai/setup-once.php?token=...
 *
 * Database SUDAH ditangani lewat import pendaftaran-pai-database.sql ke
 * phpMyAdmin (lihat deploy/README.md) -- script ini TIDAK menjalankan
 * migrate lagi, cuma symlink.
 *
 * ⚠️ WAJIB DIHAPUS setelah sukses dipakai -- script ini otomatis coba
 * hapus dirinya sendiri begitu sukses, tapi CEK LAGI manual lewat File
 * Manager kalau-kalau auto-delete-nya gagal (mis. permission).
 *
 * ⚠️ GANTI TOKEN DI BAWAH sebelum upload -- repo ini PUBLIK di GitHub,
 * jangan pernah commit token asli ke git. Edit baris di bawah lewat
 * editor teks Plesk File Manager SETELAH upload, isi string acak sendiri
 * (boleh asal-asalan, yang penting cuma kamu yang tahu).
 */
const TOKEN = 'GANTI_DENGAN_STRING_ACAK_KAMU_SENDIRI';

if (($_GET['token'] ?? '') !== TOKEN) {
    http_response_code(403);
    exit('Token salah/tidak ada. Buka dengan ?token=...');
}

header('Content-Type: text/plain; charset=utf-8');

$appBase = __DIR__.'/../../pendaftaran-pai-app';

echo "=== Setup pendaftaran-pai (symlink storage) ===\n\n";

if (! is_dir($appBase)) {
    exit("GAGAL: folder app tidak ketemu di {$appBase}\n".
        "Pastikan kode Laravel sudah diupload+extract ke httpdocs/pendaftaran-pai-app/ dulu.\n");
}

$ok = true;
$target = $appBase.'/storage/app/public';
$link = __DIR__.'/storage';

if (! is_dir($target)) {
    echo "[GAGAL] Folder target {$target} tidak ada. Cek lagi upload kode app-nya.\n\n";
    $ok = false;
} elseif (file_exists($link) || is_link($link)) {
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

echo "=== Hasil akhir ===\n";
if ($ok) {
    echo "SUKSES.\n";
    if (@unlink(__FILE__)) {
        echo "File ini sudah otomatis terhapus. Selesai!\n";
    } else {
        echo "PENTING: hapus file ini MANUAL lewat File Manager sekarang juga\n".
            "(httpdocs/public/pendaftaran-pai/setup-once.php) -- auto-delete gagal.\n";
    }
} else {
    echo "Ada langkah yang GAGAL (lihat di atas). File ini BELUM dihapus,\n".
        "perbaiki dulu masalahnya lalu refresh halaman ini buat coba lagi.\n";
}
