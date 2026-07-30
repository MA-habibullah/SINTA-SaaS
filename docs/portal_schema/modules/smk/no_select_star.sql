-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema SMK
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── smk.mitra_dudi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM smk.mitra_dudi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_perusahaan_dudi, t.bidang_usaha, t.alamat_lengkap, t.kontak_person_nama
FROM smk.mitra_dudi t
WHERE t.tenant_id = $1;

-- ── smk.pkl_penempatan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM smk.pkl_penempatan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.mitra_dudi_id, t.tahun_ajaran_id, t.pembimbing_sekolah_ptk_id
FROM smk.pkl_penempatan t
WHERE t.tenant_id = $1;

-- ── smk.pkl_jurnal_harian ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM smk.pkl_jurnal_harian;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.pkl_penempatan_id, t.tgl_jurnal, t.jam_mulai, t.jam_selesai
FROM smk.pkl_jurnal_harian t
WHERE t.tenant_id = $1;

-- ── smk.pkl_penilaian ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM smk.pkl_penilaian;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.pkl_penempatan_id, t.nilai_aspek_teknis_keahlian, t.nilai_aspek_softskill_disiplin, t.nilai_laporan_pkl
FROM smk.pkl_penilaian t
WHERE t.tenant_id = $1;

-- ── smk.ukk_penilaian ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM smk.ukk_penilaian;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tahun_ajaran_id, t.skema_sertifikasi_nama, t.penguji_internal_ptk_id
FROM smk.ukk_penilaian t
WHERE t.tenant_id = $1;
