-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema BK
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── bk.pilihan_penjurusan ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM bk.pilihan_penjurusan;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.siswa_id, t.tenant_id, t.id_jurusan, t.id_tahun_ajaran, t.status
FROM bk.pilihan_penjurusan t
WHERE t.tenant_id = $1;

-- ── bk.master_jalur_masuk ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM bk.master_jalur_masuk;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_jalur, t.kategori, t.created_at, t.updated_at
FROM bk.master_jalur_masuk t
WHERE t.tenant_id = $1;

-- ── bk.catatan_bk ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM bk.catatan_bk;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.siswa_id, t.tenant_id, t.id_guru_bk, t.tanggal_konseling, t.jenis_kasus
FROM bk.catatan_bk t
WHERE t.tenant_id = $1;

-- ── bk.master_pelanggaran ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM bk.master_pelanggaran;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.kategori, t.nama_pelanggaran, t.bobot_poin, t.created_at
FROM bk.master_pelanggaran t
WHERE t.tenant_id = $1;

-- ── bk.pelanggaran_siswa ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM bk.pelanggaran_siswa;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tgl_pelanggaran, t.master_pelanggaran_id, t.nama_pelanggaran
FROM bk.pelanggaran_siswa t
WHERE t.tenant_id = $1;

-- ── bk.tindak_lanjut_sanksi ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM bk.tindak_lanjut_sanksi;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.tahun_ajaran_id, t.tanggal_tindakan, t.jenis_tindakan
FROM bk.tindak_lanjut_sanksi t
WHERE t.tenant_id = $1;

-- ── bk.pembinaan_monitoring ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM bk.pembinaan_monitoring;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.guru_bk_id, t.tanggal_bimbingan, t.jenis_bimbingan
FROM bk.pembinaan_monitoring t
WHERE t.tenant_id = $1;

-- ── bk.sesi_mentoring ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM bk.sesi_mentoring;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.monitoring_id, t.kepsek_id, t.tanggal_sesi, t.catatan_fakta
FROM bk.sesi_mentoring t
WHERE t.tenant_id = $1;
