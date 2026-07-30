-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema KEPEGAWAIAN
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── kepegawaian.ptk_identitas ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.ptk_identitas;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.user_id, t.nik, t.nip, t.nuptk
FROM kepegawaian.ptk_identitas t
WHERE t.tenant_id = $1;

-- ── kepegawaian.riwayat_kepangkatan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.riwayat_kepangkatan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ptk_id, t.pangkat_golongan, t.nomor_sk, t.tgl_sk
FROM kepegawaian.riwayat_kepangkatan t
WHERE t.tenant_id = $1;

-- ── kepegawaian.sertifikasi_ptk ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.sertifikasi_ptk;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ptk_id, t.jenis_sertifikasi, t.nomor_sertifikat, t.nrg
FROM kepegawaian.sertifikasi_ptk t
WHERE t.tenant_id = $1;

-- ── kepegawaian.diklat_pelatihan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.diklat_pelatihan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ptk_id, t.nama_diklat, t.penyelenggara, t.peran
FROM kepegawaian.diklat_pelatihan t
WHERE t.tenant_id = $1;

-- ── kepegawaian.riwayat_pendidikan_ptk ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.riwayat_pendidikan_ptk;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ptk_id, t.jenjang_pendidikan, t.gelar_akademik, t.nama_perguruan_tinggi
FROM kepegawaian.riwayat_pendidikan_ptk t
WHERE t.tenant_id = $1;

-- ── kepegawaian.riwayat_kepala_sekolah ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.riwayat_kepala_sekolah;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ptk_id, t.nama_kepsek, t.nip_kepsek, t.nomor_sk_kepsek
FROM kepegawaian.riwayat_kepala_sekolah t
WHERE t.tenant_id = $1;

-- ── kepegawaian.dokumen_ptk ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.dokumen_ptk;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ptk_id, t.jenis_dokumen, t.nama_dokumen, t.nomor_dokumen
FROM kepegawaian.dokumen_ptk t
WHERE t.tenant_id = $1;

-- ── kepegawaian.lowongan_kerja ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.lowongan_kerja;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.judul_lowongan, t.kode_lowongan, t.posisi_jabatan, t.kualifikasi_pendidikan
FROM kepegawaian.lowongan_kerja t
WHERE t.tenant_id = $1;

-- ── kepegawaian.pelamar_kerja ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.pelamar_kerja;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.lowongan_id, t.nik, t.nama_lengkap, t.jenis_kelamin
FROM kepegawaian.pelamar_kerja t
WHERE t.tenant_id = $1;

-- ── kepegawaian.tahapan_seleksi_pelamar ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kepegawaian.tahapan_seleksi_pelamar;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.pelamar_id, t.tahapan, t.jadwal_pelaksanaan, t.lokasi_ruangan
FROM kepegawaian.tahapan_seleksi_pelamar t
WHERE t.tenant_id = $1;
