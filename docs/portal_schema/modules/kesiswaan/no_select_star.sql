-- ════════════════════════════════════════════════════
-- NO SELECT * GUIDE: Schema KESISWAAN
-- Perbandingan: ❌ SELECT * vs ✅ Explicit Columns
-- ════════════════════════════════════════════════════

-- ── kesiswaan.master_ekskul ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.master_ekskul;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.nama_ekskul, t.kategori, t.pembina_id, t.created_at
FROM kesiswaan.master_ekskul t
WHERE t.tenant_id = $1;

-- ── kesiswaan.anggota_ekskul ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.anggota_ekskul;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ekskul_id, t.siswa_id, t.tahun_ajaran_id, t.created_at
FROM kesiswaan.anggota_ekskul t
WHERE t.tenant_id = $1;

-- ── kesiswaan.nilai_ekskul ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.nilai_ekskul;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ekskul_id, t.siswa_id, t.tahun_ajaran_id, t.nilai
FROM kesiswaan.nilai_ekskul t
WHERE t.tenant_id = $1;

-- ── kesiswaan.kunci_ekskul ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.kunci_ekskul;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ekskul_id, t.tahun_ajaran_id, t.semester, t.kunci_anggota
FROM kesiswaan.kunci_ekskul t
WHERE t.tenant_id = $1;

-- ── kesiswaan.jadwal_ekskul ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.jadwal_ekskul;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ekskul_id, t.hari, t.waktu_mulai, t.waktu_selesai
FROM kesiswaan.jadwal_ekskul t
WHERE t.tenant_id = $1;

-- ── kesiswaan.jurnal_ekskul ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.jurnal_ekskul;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.ekskul_id, t.tanggal_kegiatan, t.materi, t.foto_kegiatan
FROM kesiswaan.jurnal_ekskul t
WHERE t.tenant_id = $1;

-- ── kesiswaan.data_pembina ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.data_pembina;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.user_id, t.no_hp, t.alamat, t.instansi_asal
FROM kesiswaan.data_pembina t
WHERE t.tenant_id = $1;

-- ── kesiswaan.prestasi_siswa ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.prestasi_siswa;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.tahun_ajaran_id, t.semester, t.bidang_lomba, t.nama_lomba
FROM kesiswaan.prestasi_siswa t
WHERE t.tenant_id = $1;

-- ── kesiswaan.pendaftaran_spmb ──
-- ❌ DILARANG — SELECT * menarik semua kolom (termasuk kolom sensitif!)
SELECT * FROM kesiswaan.pendaftaran_spmb;

-- ✅ WAJIB — Pilih kolom yang dibutuhkan secara eksplisit
SELECT t.id, t.tenant_id, t.siswa_id, t.nomor_pendaftaran, t.jalur_pendaftaran, t.status_pendaftaran
FROM kesiswaan.pendaftaran_spmb t
WHERE t.tenant_id = $1;
