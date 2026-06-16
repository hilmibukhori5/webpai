<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/**
 * Front controller stub untuk deploy Laravel #2 ("pendaftaran-pai") di
 * subfolder https://math.ub.ac.id/pendaftaran-pai, di server Plesk yang
 * docroot domainnya sudah terkunci punya Laravel #1 (httpdocs/public).
 *
 * File ini SENGAJA bukan bagian dari git repo Laravel #2 -- dia tinggal di
 * lokasi fisik terpisah (httpdocs/public/pendaftaran-pai/), bukan di
 * httpdocs/pendaftaran-pai-app/public/ seperti index.php Laravel bawaan.
 * Makanya semua path di bawah diarahkan naik 2 folder ($appBase) ke lokasi
 * kode Laravel-nya. Lihat deploy/README.md buat detail langkah deploy-nya.
 */
define('LARAVEL_START', microtime(true));

$appBase = __DIR__.'/../../pendaftaran-pai-app';

// Maintenance mode...
if (file_exists($maintenance = $appBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader...
require $appBase.'/vendor/autoload.php';

// Bootstrap Laravel...
/** @var Application $app */
$app = require_once $appBase.'/bootstrap/app.php';

// Folder public fisiknya BUKAN child "public/" dari app root (lihat catatan
// di atas) -- kasih tahu Laravel public_path() yang benar (folder ini
// sendiri), supaya kalau ada kode yang nanti panggil public_path()/asset()
// hasilnya tetap konsisten. (Storage::url() di app ini sudah aman duluan
// karena dibangun dari APP_URL langsung, lihat config/filesystems.php.)
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
