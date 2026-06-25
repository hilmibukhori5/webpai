# Rekap Test Case — EligibilityService

File: `tests/Feature/Services/EligibilityServiceTest.php`
Terakhir diupdate: 2026-06-22

---

## Aturan Dasar (Decision Tree)

| Kondisi | Decision | Biaya |
|---|---|---|
| weighted avg > 3,5 (Adendum PKS Lama) | **lama** | Rp500.000 |
| weighted avg ≤ 3,5 TAPI NA ≥ persentil (PKS Baru) | **baru** | Rp550.000 |
| Tidak memenuhi keduanya | **none** | — |

**Catatan:**
- **Adendum PKS Lama** berlaku untuk semua kode kurikulum (lama maupun baru)
- **PKS Baru diblokir** jika ada nilai dari TA 23/24 atau lebih lama (`forceOldScheme=true`)
- Adendum PKS Lama **diutamakan** jika keduanya lolos

---

## Tabel Test Case

| No | Skenario | Prodi | Modul | Kursus (Kode Kurikulum) | TA Nilai | NH → grade_point | NA vs Threshold | forceOldScheme | eligibleLama | eligibleBaru | Decision | Biaya | Catatan |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | PKS Baru murni | Aktuaria | A10 | MAA62043 + MAA61041 (BARU) | 24/25 | C → 2,0 | 85,90 ≥ 80 ✓ | false | ❌ (avg 2,0 ≤ 3,5) | ✅ | **baru** | 550.000 | NH sengaja rendah agar weighted avg gagal → PKS Baru bisa terpilih |
| 2 | Lolos keduanya → prioritas Adendum PKS Lama | Aktuaria | A10 | MAA62009 + MAA61015 (LAMA) | 24/25 | A → 4,0 | 90,88 ≥ 80 ✓ | false | ✅ | ✅ | **lama** | 500.000 | Lolos keduanya; lama diutamakan karena lebih murah |
| 3 | Adendum PKS Lama, kode lama, nilai lama | Aktuaria | A10 | MAA62009 + MAA61015 (LAMA) | 22/23 | A + B+ → 3,75 | Tidak ada threshold | true | ✅ | ❌ (diblokir) | **lama** | 500.000 | forceOldScheme memblokir PKS Baru |
| 4 | Adendum PKS Lama, kode BARU, nilai baru | Aktuaria | A10 | MAA62043 + MAA61041 (BARU) | 24/25 | A → 4,0 | 70,70 (tidak ada threshold) | false | ✅ | ❌ (no threshold) | **lama** | 500.000 | Kode baru tidak memblokir Adendum PKS Lama (rule b) |
| 5 | Komponen tidak lengkap | Aktuaria | A10 | Hanya MAA62043 (BARU) | 22/23 | A → 4,0 | — | — | ❌ | ❌ | **none** | — | Komponen kurang → matchedSets kosong |
| 6 | Nilai E pada satu komponen | Aktuaria | A10 | MAA62043 (A) + MAA61041 (E) | 22/23 | A + E | — | — | ❌ | ❌ | **none** | — | NH=E langsung didiskualifikasi |
| 7 | Weighted avg tepat 3,5 (batas TIDAK lolos) | Aktuaria | A10 | MAA62009 + MAA61015 (LAMA) | 22/23 | B+ + B+ → 3,5 | Tidak ada threshold | true | ❌ (avg = 3,5, perlu LEBIH DARI) | ❌ | **none** | — | Syarat: strictly > 3,5 |
| 8 | Modul A20, kode shared | Aktuaria | A20 | MAA62003 + MAA61007 | 22/23 | A + B+ → 3,75 | Tidak ada threshold | true | ✅ | ❌ | **lama** | 500.000 | Kode shared di dua kurikulum |
| 9 | Retake — pakai NA tertinggi | Aktuaria | A10 | MAA62043 (retake) + MAA61041 (BARU) | Best: 24/25 | Best: A → 4,0 | 90,85 ≥ 80 ✓ | false (best grade 24/25) | ✅ | ✅ (bukti NA=90 dipakai) | **lama** | 500.000 | MAA62043 punya 2 baris (NA=60 dan NA=90); sistem pakai NA=90 terbukti dari eligibleBaru=true |
| 10 | TA 23/24 blokir PKS Baru (kode baru) | Aktuaria | A10 | MAA62043 + MAA61041 (BARU) | 23/24 | A → 4,0 | 95,92 ≥ 80 (ada threshold) | true | ✅ | ❌ (diblokir) | **lama** | 500.000 | Meski NA jauh di atas threshold, PKS Baru diblokir karena tahun |
| 11 | Campuran TA (satu lama, satu baru) | Aktuaria | A10 | MAA62043 (BARU) + MAA61041 (BARU) | 23/24 & 24/25 | A → 4,0 | 90,88 ≥ 80 | true (satu nilai lama cukup) | ✅ | ❌ (diblokir) | **lama** | — | "Any old year" → forceOldScheme aktif |
| 12 | TA 22/23, kode BARU | Aktuaria | A10 | MAA62043 + MAA61041 (BARU) | 22/23 | A → 4,0 | Tidak ada threshold | true | ✅ | ❌ | **lama** | 500.000 | Nilai sangat lama + kode baru tetap → lama (forceOldScheme) |
| 13 | S1 Matematika — pakai kursus MAM | Matematika | A20 | MAM60601 + MAM60602 | 22/23 | A → 4,0 | Tidak ada threshold | true | ✅ | ❌ | **lama** | — | Matematika hanya cocokkan kursus MAM, bukan MAA |
| 14 | S1 Matematika — kursus MAA tidak cocok | Matematika | A20 | MAA62003 + MAA61007 (kursus Aktuaria) | 24/25 | A → 4,0 | — | — | ❌ | ❌ | **none** | — | Tidak ada matched set untuk Matematika |
| 15 | Struktur componentGrades (untuk admin) | Aktuaria | A10 | MAA62043 + MAA61041 (BARU) | 24/25 | A → 4,0 | 85,90 ≥ 80 ✓ | false | ✅ | ✅ | **lama** | — | Hanya cek struktur componentGrades['baru'], tidak cek decision |

---

## Ringkasan per Cabang Decision Tree

| Cabang | Test No. | Kondisi Utama |
|---|---|---|
| PKS Baru | 1 | Nilai baru (24/25+), weighted avg ≤ 3,5, NA ≥ threshold |
| Adendum PKS Lama (kedua lolos, lama menang) | 2 | Nilai baru, kode lama, lolos keduanya |
| Adendum PKS Lama (kode lama + nilai lama) | 3 | forceOldScheme=true, lolos 4b |
| Adendum PKS Lama (kode BARU + nilai baru) | 4 | rule b: kode tidak memblokir |
| None — komponen tidak lengkap | 5 | Satu matkul belum diambil |
| None — nilai E | 6 | NH=E pada satu komponen |
| None — weighted avg tepat 3,5 | 7 | Harus LEBIH DARI, bukan sama dengan |
| Adendum PKS Lama (modul A20) | 8 | Kode shared, forceOldScheme |
| Retake — pakai NA tertinggi | 9 | `bestGradeFor` pilih NA=90 bukan NA=60 |
| forceOldScheme blokir PKS Baru (TA 23/24) | 10 | Nilai TA 23/24 → PKS Baru diblokir |
| forceOldScheme (campuran tahun) | 11 | Satu nilai lama sudah cukup blokir PKS Baru |
| forceOldScheme (TA 22/23, kode baru) | 12 | Tahun sangat lama + kode baru tetap → lama |
| Prodi Matematika — kursus MAM | 13 | Matching berdasarkan prodi |
| Prodi Matematika — kursus MAA tidak cocok | 14 | Cross-prodi tidak matched |
| componentGrades tersedia | 15 | Struktur output untuk UI admin |

---

## Hal yang Perlu Dikonfirmasi / Potensi Perlu Ditambah

| # | Pertanyaan |
|---|---|
| A | Test 9 (retake): komentar lama bilang "PKS Baru bisa berlaku" tapi sekarang lama yang menang. Apakah ada skenario retake yang expected-nya tetap **baru**? Misalnya retake dengan NH rendah (grade_point ≤ 3,5)? |
| B | Test 13 (Matematika, A20): komentar kode bilang "pakai TA 22/23 supaya forceOldScheme=true". Apakah ada test untuk Matematika + nilai TA 24/25 + weighted avg lolos → harusnya tetap **lama** (rule b)? Saat ini belum ada. |
| C | Belum ada test untuk skenario: **semua komponen nilai E** (bukan hanya satu). |
| D | Belum ada test untuk skenario: **modul A30–A70** (semua test pakai A10 atau A20). |
| E | Belum ada test: NA **tepat di batas threshold** (NA = threshold_na, syarat ≥ berarti harus lolos). |
