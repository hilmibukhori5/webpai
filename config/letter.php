<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kop Surat & Penandatangan — Surat Keterangan Penyetaraan PAI
    |--------------------------------------------------------------------------
    |
    | Data tetap (sama untuk semua surat), dipakai App\Documents\
    | EquivalencyLetterDocument. Ubah di sini kalau ada pergantian
    | pejabat/instansi -- tidak perlu sentuh code.
    |
    */

    'letterhead' => [
        'ministry' => 'KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET DAN TEKNOLOGI',
        'university' => 'UNIVERSITAS BRAWIJAYA',
        'faculty' => 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
        'address' => 'Jl. Veteran, Malang 65145, Indonesia',
        'phone' => 'Telp./Fax. (0341) 554403, 551611',
        'email' => 'email: mipa@ub.ac.id',
        'website' => 'http://mipa.ub.ac.id',
    ],

    'hal' => 'Penyetaraan Kurikulum Persyaratan Aktuaris Indonesia',

    /*
    |--------------------------------------------------------------------------
    | Rekening Pembayaran — Formulir Permohonan Penyetaraan
    |--------------------------------------------------------------------------
    |
    | Dipakai App\Documents\EquivalencyFormDocument untuk kolom "Ditujukan ke
    | Rekening". Berlaku untuk semua skema (PKS Lama maupun PKS Baru).
    | Update di sini kalau ada perubahan rekening PAI.
    |
    */
    'form' => [
        'bank_name' => 'Bank Mandiri Cabang Tebet Raya',
        'bank_no' => '124-0000-555-772',
        'bank_holder' => 'Persatuan Aktuaris Indonesia',
    ],

    'signer' => [
        'name' => "Dr. Sa'adatul Fitri, S.Si., M.Sc.",
        'nip' => '198006142005012004',
        'jabatan' => 'Ketua Departemen Matematika',
        'instansi' => 'Fakultas MIPA Universitas Brawijaya',
    ],

];
