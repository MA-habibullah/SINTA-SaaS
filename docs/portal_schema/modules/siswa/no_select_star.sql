-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema SISWA
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── siswa.siswa ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.siswa;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.user_id, t.nisn, t.nis, t.nama_lengkap
FROM siswa.siswa t
WHERE t.tenant_id = $1;

-- ── siswa.fisik_kesehatan_siswa ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.fisik_kesehatan_siswa;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tinggi_badan_cm, t.berat_badan_kg, t.lingkar_kepala_cm
FROM siswa.fisik_kesehatan_siswa t
WHERE t.tenant_id = $1;

-- ── siswa.orang_tua ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.orang_tua;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id_orang_tua, t.siswa_id, t.id_tempat_lahir_ayah, t.nik_ayah, t.nama_ayah, t.tahun_lahir_ayah
FROM siswa.orang_tua t
WHERE t.tenant_id = $1;

-- ── siswa.registrasi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.registrasi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id_registrasi, t.siswa_id, t.jalur_diterima, t.jenis_pendaftaran, t.tanggal_masuk, t.paud_formal
FROM siswa.registrasi t
WHERE t.tenant_id = $1;

-- ── siswa.absensi_semester ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.absensi_semester;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tahun_ajaran_id, t.semester, t.sakit
FROM siswa.absensi_semester t
WHERE t.tenant_id = $1;

-- ── siswa.riwayat_kenaikan_kelas ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.riwayat_kenaikan_kelas;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.jenis_aksi, t.id_kelas_asal, t.id_kelas_tujuan
FROM siswa.riwayat_kenaikan_kelas t
WHERE t.tenant_id = $1;

-- ── siswa.riwayat_beasiswa ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.riwayat_beasiswa;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.jenis_beasiswa, t.sumber, t.tahun_menerima
FROM siswa.riwayat_beasiswa t
WHERE t.tenant_id = $1;

-- ── siswa.anggota_kelas ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.anggota_kelas;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.semester_id, t.rombongan_belajar_id, t.siswa_id, t.no_urut_presensi
FROM siswa.anggota_kelas t
WHERE t.tenant_id = $1;

-- ── siswa.dokumen ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM siswa.dokumen;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id_dokumen, t.siswa_id, t.berkas_kk, t.berkas_akta, t.berkas_ijazah_sd, t.berkas_ijazah_smp
FROM siswa.dokumen t
WHERE t.tenant_id = $1;
