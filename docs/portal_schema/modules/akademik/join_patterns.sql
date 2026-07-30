-- ════════════════════════════════════════════════════
-- JOIN PATTERNS: Schema AKADEMIK
-- Panduan JOIN yang benar berdasarkan FK Constraint
-- ════════════════════════════════════════════════════

-- ── Tabel: akademik.tahun_ajaran ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.tahun_ajaran
SELECT
    t.id,
    t.tenant_id,
    t.tahun_ajaran,
    t.is_active,
    t.created_at,
    t.updated_at,
    t.deleted_at
FROM akademik.tahun_ajaran t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.angkatan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.angkatan
SELECT
    t.id,
    t.tenant_id,
    t.tahun_angkatan,
    t.is_active,
    t.created_at,
    t.updated_at,
    t.deleted_at
FROM akademik.angkatan t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.pendidikan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.pendidikan
SELECT
    t.id,
    t.tenant_id,
    t.kode_pendidikan,
    t.nama_pendidikan,
    t.is_active,
    t.created_at,
    t.updated_at,
    t.deleted_at
FROM akademik.pendidikan t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.ref_kurikulum ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.ref_kurikulum
SELECT
    t.id,
    t.tenant_id,
    t.nama_kurikulum,
    t.tipe_penilaian,
    t.is_active,
    t.created_at,
    t.deleted_at
FROM akademik.ref_kurikulum t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.jurusan ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.jurusan
SELECT
    t.id,
    t.tenant_id,
    t.kode_jurusan,
    t.nama_jurusan,
    t.is_active,
    t.created_at,
    t.updated_at,
    t.deleted_at
FROM akademik.jurusan t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.kelas ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.kelas
SELECT
    t.id,
    t.tenant_id,
    t.id_jenjang,
    t.id_jurusan,
    t.kode_kelas,
    t.nama_kelas,
    t.is_active,
    t.created_at
FROM akademik.kelas t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN jenjang jenj ON t.id_jenjang = jenj.id
INNER JOIN jurusan juru ON t.id_jurusan = juru.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.kelas_kurikulum ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.kelas_kurikulum
SELECT
    t.id,
    t.tenant_id,
    t.kelas_id,
    t.tahun_ajaran,
    t.kurikulum_id,
    t.created_at,
    t.is_locked
FROM akademik.kelas_kurikulum t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN kelas kela ON t.kelas_id = kela.id
INNER JOIN ref_kurikulum ref_ ON t.kurikulum_id = ref_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.mata_pelajaran ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.mata_pelajaran
SELECT
    t.id,
    t.tenant_id,
    t.kode_mapel,
    t.nama_mapel,
    t.is_active,
    t.created_at,
    t.updated_at,
    t.deleted_at
FROM akademik.mata_pelajaran t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.pemetaan_mapel ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.pemetaan_mapel
SELECT
    t.id,
    t.tenant_id,
    t.tahun_ajaran,
    t.semester,
    t.kelas_id,
    t.kelompok_id,
    t.mapel_id,
    t.created_at
FROM akademik.pemetaan_mapel t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN kelas kela ON t.kelas_id = kela.id
INNER JOIN mata_pelajaran mata ON t.mapel_id = mata.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.kunci_akademik ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.kunci_akademik
SELECT
    t.id,
    t.tenant_id,
    t.tahun_ajaran,
    t.semester,
    t.is_locked_kurikulum,
    t.is_locked_nilai,
    t.created_at,
    t.updated_at
FROM akademik.kunci_akademik t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.detail_nilai_rapor ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.detail_nilai_rapor
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.kelas_id,
    t.tahun_ajaran,
    t.semester,
    t.mapel_id,
    t.nilai_akhir
FROM akademik.detail_nilai_rapor t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
INNER JOIN kelas kela ON t.kelas_id = kela.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.log_nilai_rapor ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.log_nilai_rapor
SELECT
    t.id,
    t.tenant_id,
    t.user_id,
    t.siswa_id,
    t.mapel_id,
    t.semester,
    t.tahun_ajaran,
    t.nilai_lama_json
FROM akademik.log_nilai_rapor t
INNER JOIN tenants tena ON t.tenant_id = tena.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.nilai_sikap_k13 ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.nilai_sikap_k13
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tahun_ajaran,
    t.semester,
    t.predikat_spiritual,
    t.deskripsi_spiritual,
    t.predikat_sosial
FROM akademik.nilai_sikap_k13 t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.nilai_p5 ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.nilai_p5
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tahun_ajaran_id,
    t.semester,
    t.nama_projek,
    t.deskripsi_projek,
    t.kualifikasi_karakter
FROM akademik.nilai_p5 t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.nilai_ujian_sekolah ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.nilai_ujian_sekolah
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.mata_pelajaran_id,
    t.nilai_ujian,
    t.tahun_ajaran,
    t.created_at,
    t.updated_at
FROM akademik.nilai_ujian_sekolah t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.id_siswa = sisw.id
INNER JOIN mata_pelajaran mata ON t.id_mata_pelajaran = mata.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.catatan_wali_kelas ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.catatan_wali_kelas
SELECT
    t.id,
    t.tenant_id,
    t.siswa_id,
    t.tahun_ajaran_id,
    t.semester,
    t.catatan,
    t.created_at
FROM akademik.catatan_wali_kelas t
INNER JOIN tenants tena ON t.tenant_id = tena.id
INNER JOIN siswa sisw ON t.siswa_id = sisw.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.semester ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.semester
SELECT
    t.id,
    t.tenant_id,
    t.tahun_ajaran_id,
    t.nama_semester,
    t.semester,
    t.tgl_mulai,
    t.tgl_selesai,
    t.is_active
FROM akademik.semester t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN akademik.tahun_ajaran tahu ON t.tahun_ajaran_id = tahu.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.penugasan_mengajar ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.penugasan_mengajar
SELECT
    t.id,
    t.tenant_id,
    t.semester_id,
    t.guru_ptk_id,
    t.rombongan_belajar_id,
    t.mata_pelajaran_id,
    t.jumlah_jam_tatap_muka_jtm,
    t.sk_mengajar_no
FROM akademik.penugasan_mengajar t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN akademik.semester seme ON t.semester_id = seme.id
INNER JOIN kepegawaian.ptk_identitas ptk_ ON t.guru_ptk_id = ptk_.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.nilai_sumatif ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.nilai_sumatif
SELECT
    t.id,
    t.tenant_id,
    t.semester_id,
    t.rombongan_belajar_id,
    t.mata_pelajaran_id,
    t.siswa_id,
    t.jenis_sumatif,
    t.target_capaian_tp_id
FROM akademik.nilai_sumatif t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN akademik.semester seme ON t.semester_id = seme.id
INNER JOIN akademik.rombongan_belajar romb ON t.rombongan_belajar_id = romb.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;

-- ── Tabel: akademik.target_capaian_tp ──────────────────
-- ✅ SELECT EXPLICIT COLUMNS — Dilarang menggunakan SELECT *
-- Tabel: akademik.target_capaian_tp
SELECT
    t.id,
    t.tenant_id,
    t.mata_pelajaran_id,
    t.tingkat_kelas,
    t.kode_tp,
    t.deskripsi_capaian_tp,
    t.elemen_capaian,
    t.semester_default
FROM akademik.target_capaian_tp t
INNER JOIN core.tenants tena ON t.tenant_id = tena.id
INNER JOIN akademik.mata_pelajaran mata ON t.mata_pelajaran_id = mata.id
WHERE t.tenant_id = $1   -- ✅ Gunakan index idx_*_tenant
  AND t.is_active = true
ORDER BY t.created_at DESC
LIMIT 50 OFFSET 0;
